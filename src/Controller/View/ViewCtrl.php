<?php
namespace Controller\View;

use Controller\View\BaseCtrl;
use Entity\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Controller\Steam\SteamCtrl;
use Entity\Tournament;
use Controller\Tournament\TournamentCtrl;

/**
* Template render
*/
class ViewCtrl extends BaseCtrl
{
    public function showIndex($req, $res, $arg)
    {
        return $this->view->render($res, 'index.html.twig', [
           
        ]);
    }

    public function showLogin($req, $res, $arg)
    {
        return $this->view->render($res, 'login.html.twig');
    }

    public function showUser($req, $res, $arg)
    {
        $user = User::where('slug', $arg['slug'])->first();
        if ($user) { //first or fail!
            if ($this->checkDate($user->updated_at)) {
                $stats = SteamCtrl::stats($user->steam_id, $this->hybridConfig['Steam']['keys']['secret']);
                $user->mmr_dm = $stats['mmr_dm']; 
                $user->mmr_rm = $stats['mmr_rm']; 
                $user->games = $stats['games'];
                $user->save();
            }
            return $this->view->render($res, 'user.html.twig', [
                'user' => $user
            ]);
        } else {
            return $res->withStatus(404);
        }
    }

    public function showTournaments($req, $res, $arg)
    {
        $user = User::find($this->userData->id);
        if ($this->checkDate($user->updated_at)) {
            $stats = SteamCtrl::stats($user->steam_id, $this->hybridConfig['Steam']['keys']['secret']);
            //$steam = new SteamCtrl;
            //$stats = $steam->stats($user->steam_id);
            $user->mmr_dm = $stats['mmr_dm'];
            $user->mmr_rm = $stats['mmr_rm'];
            $user->save();
        }
        $tournaments = Tournament::with(['teams' => function($query) {
                    $query->with(['members' => function($query) {
                        $query->select('id', 'username', 'slug', 'avatar', 'profileurl', 'mmr_dm', 'mmr_rm', 'games');
                    }]);
                    $query->withCount('members');
                }])
                ->withCount('teams')
                ->get();
        return $this->view->render($res, 'tournaments.html.twig', [
            'user' => $user,
            'tournaments' => $tournaments
        ]);
    }

    public function showNewTournament($req, $res, $arg)
    {
        return $this->view->render($res, 'new_tournament.html.twig');
    }

    public function showTournament($req, $res, $arg)
    {
        //$user = User::find($this->userData->id);
        $user = User::whereHas('teams', function ($query) use ($arg) {
                $query->where('tournament_id', $arg['id']);
            })
            ->where('id', $this->userData->id)
            ->get();
        
        try {
            $tournament = Tournament::with(['teams' => function($query) {
                    $query->with(['members' => function($query) {
                        $query->select('id', 'username', 'slug', 'avatar', 'profileurl', 'mmr_dm', 'mmr_rm', 'games');
                    }]);
                    $query->withCount('members');
                }])
                ->findOrFail($arg['id']);
            $api = TournamentCtrl::tournamentDetail($arg['id'], $this->challongeKey, $this->challongeApi);
            $ready = true;
            foreach ($tournament->teams as $key => $value) {
                if ($value->members_count < $tournament->team_members) {
                    $ready = false;
                    break;
                }
            }
            return $this->view->render($res, 'tournament.html.twig', [
                'tournament' => $tournament,
                'joined' => !!$user,
                'api' => $api,
                'ready' => $ready
            ]);
        } catch (ModelNotFoundException $ex) {
            $resData = array(
                'message' => 'Get Tournament Error: ' . $ex->getMessage()
            );
            return $res->withJson($resData, 404); //pagina 404 da fare!
        }
    }
}