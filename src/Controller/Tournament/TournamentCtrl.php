<?php
namespace Controller\Tournament;

use Controller\Tournament\BaseCtrl;
use Entity\User;
use Entity\Team;
use Entity\Tournament;

/**
* Manage tournaments
*/
class TournamentCtrl extends BaseCtrl
{
    public function create($req, $res, $arg)
    {
        $body = $req->getParsedBody();
        $tournamentData = [
            'api_key' => $this->challongeKey,
            'tournament[name]' => $body['title'],
            //'tournament[description]' => $body['description'] ? $body['description'] : "",
            'tournament[open_signup]' => false,
            'tournament[private]' => true,
            "tournament[tournament_type]" => $body['tournament_type'],
            "tournament[url]" => "aoe2ita_" . time(), //randomizzare l'url
            "tournament[hold_third_place_match]" => true
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $this->challongeApi . 'tournaments.json',
            CURLOPT_POSTFIELDS => http_build_query($tournamentData),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $respTournament = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $this->logger->error($err);
            return $res->withStatus(500);
        }
        $curl = null;
        $tournament = new Tournament;
        $tournament->id = $respTournament->tournament->id;
        $tournament->title = $respTournament->tournament->name;
        $tournament->n_teams = $body['n_teams'];
        $tournament->team_members = $body['team_members'];
        $tournament->type = $respTournament->tournament->tournament_type;
        $tournament->description = $body['description'];
        $tournament->svg = $respTournament->tournament->live_image_url;
        $tournament->host = $body['host'];
        $tournament->rank = $body['rank_type'];
        $tournament->rank_min = $body['rank_min'];
        $tournament->rank_max = $body['rank_max'];
        $tournament->random_team = $body['random'] ? true : false;
        $tournament->rounds = (int)log($body['n_teams'], 2);

        $user = User::find($this->userData->id);
        $user->tournaments()->save($tournament);

        $participants = [
            'api_key' => $this->challongeKey
        ];
        for ($i=1; $i <= (int)$tournament->n_teams; $i++) {
            $participants["participant[name]"] = 'Team ' . $i;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                //CURLOPT_SSL_VERIFYHOST => 2,
                //CURLOPT_SSL_VERIFYPEER => 2,
                CURLOPT_URL => $this->challongeApi . 'tournaments/' . $tournament->id . '/participants.json',
                CURLOPT_POSTFIELDS => http_build_query($participants),
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/x-www-form-urlencoded",
                )
            ));
            $respParticipants = json_decode(curl_exec($curl));
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $this->logger->error($err);
                return $res->withStatus(500);
            }

            $team = new Team;
            //$this->logger->info($respParticipants->participant->name);
            $team->id = $respParticipants->participant->id;
            $team->title = $respParticipants->participant->name;
            $tournament->teams()->save($team);
            $team->save();
        }
        return $res->withJson($tournament->id);
    }

    public function joinTeam($req, $res, $arg)
    {
        $user = User::find($this->userData->id);
        $user->teams()->attach($arg['teamid']);
        $team = Team::with('tournament')
            ->find($arg['teamid']);
        $mmr = $team->tournament->rank == 'DM' ? $user->mmr_dm + ($user->games * 0) : $user->mmr_rm + ($user->games * 0); //da pensare costante
        $team->average = $team->average ? ($team->average + $mmr) / 2 : $mmr;
        $team->save();
        return $res->withStatus(200);
    }

    public function joinRandomTeam($req, $res, $arg)
    {
        $user = User::find($this->userData->id);
        $tournament = Tournament::with(['teams' => function($query) {
                $query->withCount('members');
            }])
            ->findOrFail($arg['id']);
        $teamsAverage = array();
        foreach ($tournament->teams as $team) {
            if ($team->members_count == 0) {
                $user->teams()->attach($team->id);
                $team->average = $tournament->rank == 'DM' ? $user->mmr_dm + ($user->games * 0) : $user->mmr_rm + ($user->games * 0); //da pensare costante
                $team->save();
                return $res->withStatus(200);
            }
            $teamsAverage[$team->id] = $team->average;
            if ($team->members_count == $tournament->team_members) {
                $fullTeams[$team->id] = true;
            }
        }
        $average = array_sum($teamsAverage) / count($teamsAverage);
        $teamsAverage = array_diff_key($teamsAverage, $fullTeams); //rimuovo i team pieni
        $mmr = $tournament->rank == 'DM' ? $user->mmr_dm : $user->mmr_rm; 
        $teamId = $mmr > $average ? array_search(min($teamsAverage), $teamsAverage) : array_search(max($teamsAverage), $teamsAverage);
        $user->teams()->attach($teamId);
        $team = Team::find($teamId);
        $team->average = ($team->average + $mmr) / 2;
        $team->save(); 
        return $res->withStatus(200);
    }

    public function leaveTeam($req, $res, $arg)
    {
        $team = Team::withCount('members')
            ->find($arg['id']);
        if ($team->members_count == 1) {
            $team->average = 0;
        } else {
            $team->average = $team->average * 2 - $arg['mmr'];
        }
        $team->save();
        $team->members()->detach($this->userData->id);
        return $res->withStatus(200);
    }

    public function startTournament($req, $res, $arg)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $this->challongeApi . 'tournaments/' . $arg['id'] . '/start.json',
            CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $resp = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
             $this->logger->error($err);
            return $res->withStatus(500);
        }
        $tournament = Tournament::find($arg['id']);
        $tournament->status = 'underway';
        $tournament->save();
        return $res->withStatus(200);
    }
    
    public function tournamentDetail($id, $secret, $api)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $api . 'tournaments/' . $id . '.json?api_key=' . $secret,
            //CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $resp = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
             $this->logger->error($err);
            return false;
        }
        return $resp->tournament;
    }

    public function tournamentMatches($id, $secret, $api)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $api . 'tournaments/' . $id . '/matches.json?api_key=' . $secret,
            //CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $resp = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
             $this->logger->error($err);
            return false;
        }
        return $resp;
    }

    public function matchScore($req, $res, $arg)
    {
        $body = $req->getParsedBody();
        $matchData = [
            'api_key' => $this->challongeKey,
            '_method' => 'PUT',
            'match[winner_id]' => $body['teamId'],
            'match[scores_csv]' => $body['one'] . '-' . $body['two'] //da fare fronte "1-0" o "0-1"
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $this->challongeApi . 'tournaments/' . $arg['id'] . '/matches/'. $arg['match_id'] . '.json',
            CURLOPT_POSTFIELDS => http_build_query($matchData),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $resp = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
             $this->logger->error($err);
            return $res->withStatus(500);
        }
        return $res->withStatus(200);
    }

    public function deleteTournament($req, $res, $arg)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $this->challongeApi . 'tournaments/' . $arg['id'] . '.json',
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $resp = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
             $this->logger->error($err);
            return $res->withStatus(500);
        }
        $tournament = Tournament::with('teams')
            ->find($arg['id']);
        foreach ($tournament->teams as $team) {
            $team->delete();
        }
        $tournament->delete();
        return $res->withStatus(200);
    }

    public function closeTournament($req, $res, $arg)
    {
        $body = $req->getParsedBody();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $this->challongeApi . 'tournaments/' . $arg['id'] . '/finalize.json',
            CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            )
        ));
        $resp = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
             $this->logger->error($err);
            return $res->withStatus(500);
        }
        $tournament = Tournament::with(['teams' => function($query) {
                    $query->with('members');
                }])
            ->find($arg['id']);
        $tournament->status = 'complete';
        $tournament->save();
        foreach ($tournament->teams as $team) {
            foreach ($team->members as $member) {
                $member->t_completed++;
                $member->save();
            }
        }
        $teams = Team::with('members')
            ->find([$body['first'], $body['second'], $body['third']]);
        for ($i=0; $i < $tournament->team_members; $i++) { 
            $teams[0]->members[$i]->first_position++;
            $teams[0]->members[$i]->save();
            $teams[1]->members[$i]->second_position++;
            $teams[1]->members[$i]->save();
            $teams[2]->members[$i]->third_position++;
            $teams[2]->members[$i]->save();
        }
        return $res->withStatus(200);
    }

    public function deleteCompleteTournaments($req, $res, $arg)
    {
        $tournaments = Tournament::with('teams')
            ->where('status', 'complete')
            ->whereDate('updated_at', '<=', (new DateTime())->modify('-3 days')->format('Y-m-d'))
            ->get();
        foreach ($tournaments as $tournament) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                //CURLOPT_SSL_VERIFYHOST => 2,
                //CURLOPT_SSL_VERIFYPEER => 2,
                CURLOPT_URL => $this->challongeApi . 'tournaments/' . $tournament->id . '.json',
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/x-www-form-urlencoded",
                )
            ));
            curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $this->logger->error($err);
                return $res->withStatus(500);
            }
            foreach ($tournament->teams as $team) {
                $team->delete();
            }
            $user = User::find($tournament->user_id);
            $user->t_created++;
            $user->save();
            $tournament->delete();
        }
        return $res->withStatus(200);
    }
}