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
            //"tournament[tournament_type]" => "single elimination",
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
        if ($err) {
            $this->logger->error($err);
            return $res->withStatus(500);
        }
        curl_close($curl);
        $curl = null;
        $tournament = new Tournament;
        $tournament->id = $respTournament->tournament->id;
        $tournament->title = $respTournament->tournament->name;
        $tournament->n_teams = $body['n_teams'];
        $tournament->team_members = $body['team_members'];
        $tournament->type = $respTournament->tournament->tournament_type;
        $tournament->description = $respTournament->tournament->description;
        $tournament->svg = $respTournament->tournament->live_image_url;

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
            if ($err) {
                $this->logger->error($err);
                return $res->withStatus(500);
            }
            curl_close($curl);

            $team = new Team;
            $this->logger->info($respParticipants->participant->name);
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
        //$team = Team::find($arg['teamid']);
        $user->teams()->attach($arg['teamid']);
        return $res->withStatus(200);
    }
}