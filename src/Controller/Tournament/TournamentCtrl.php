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
        /*$team = Team::withCount('members')
            ->with(['tournament' => function($query) {
                $query->select('id', 'team_members');
            }])
            ->find($arg['teamid']);
        if ($team->members_count == $team->tournament->team_members) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                //CURLOPT_SSL_VERIFYHOST => 2,
                //CURLOPT_SSL_VERIFYPEER => 2,
                CURLOPT_URL => $this->challongeApi . 'tournaments/' . $team->tournament->id . '/participants/' . $team->id . '/check_in.json',
                CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->challongeKey]),
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/x-www-form-urlencoded",
                )
            ));
            $resp = json_decode(curl_exec($curl));
            $err = curl_error($curl);
            if ($err) {
                $this->logger->error($err);
                return $res->withStatus(500);
            }
            curl_close($curl);
        }*/ //da verificare se necessario
        return $res->withStatus(200);
    }

    public function startTournament($req, $res, $arg)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_SSL_VERIFYHOST => 2,
            //CURLOPT_SSL_VERIFYPEER => 2,
            CURLOPT_URL => $this->challongeApi . 'tournaments/' . $arg['id'] . '/start.json?',
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
        Tournament::destroy($arg['id']);
        return $res->withStatus(200);
    }
}