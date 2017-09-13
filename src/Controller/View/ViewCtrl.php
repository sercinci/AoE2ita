<?php
namespace Controller\View;

use Controller\View\BaseCtrl;
use Entity\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Controller\Steam\SteamCtrl;
use Entity\Tournament;
use Controller\Tournament\TournamentCtrl;
use Dflydev\FigCookies\FigRequestCookies;

/**
* Template render
*/
class ViewCtrl extends BaseCtrl
{
    public function showIndex($req, $res, $arg)
    {
        $cookie = FigRequestCookies::get($req, 'token');
        return $this->view->render($res, 'index.html.twig', [
            'user' => !!$cookie->getValue()
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
        $user = $this->getUserTournaments();
        $tournaments = Tournament::with(['teams' => function($query) {
                    $query->with(['members' => function($query) {
                        $query->select('id', 'username', 'slug', 'avatar', 'profileurl', 'mmr_dm', 'mmr_rm', 'games');
                    }]);
                    $query->withCount('members');
                }])
                ->withCount('teams')
                ->where('status', 'pending')
                ->get();
        foreach ($tournaments as $tournament) {
            $tournament->joined = 0;
            $tournament->subscribed = false;
            foreach ($user->teams as $t) {
                if ($t->tournament_id == $tournament->id) {
                    $tournament->subscribed = true;
                    break;
                }
            }
            foreach ($tournament->teams as $team) {
                $tournament->joined += $team->members_count;
            }
        }
        return $this->view->render($res, 'tournaments.html.twig', [
            'user' => $user,
            'tournaments' => $tournaments
        ]);
    }

    private function getUserTournaments()
    {
        $user = User::with(['teams' => function($query) {
                $query->with(['tournament' => function($query) {
                    $query->where('status', 'underway')
                        ->select('id', 'title')
                        ->get();
                }]);
            }])
            ->find($this->userData->id);
        if ($this->checkDate($user->updated_at)) {
            $stats = SteamCtrl::stats($user->steam_id, $this->hybridConfig['Steam']['keys']['secret']);
            $user->mmr_dm = $stats['mmr_dm'];
            $user->mmr_rm = $stats['mmr_rm'];
            $user->games = $stats['games'];
            $user->save();
        }
        return $user;
    }

    public function showNewTournament($req, $res, $arg)
    {
        $user = $this->getUserTournaments();
        return $this->view->render($res, 'new_tournament.html.twig', [
            'user' => $user
        ]);
    }

    public function showTournament($req, $res, $arg)
    {
        $user = User::find($this->userData->id);
        $member = User::whereHas('teams', function ($query) use ($arg) {
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
            $api = TournamentCtrl::tournamentDetail($arg['id'], $this->challongeKey, $this->challongeApi); //forse non serve
            $ready = true;
            $teams = array();
            foreach ($tournament->teams as $team) {
                if ($team->members_count < $tournament->team_members) {
                    $ready = false;
                    break;
                }
                $teams[$team->id] = $team;
            }
            $matches = array();
            if ($tournament->status == 'underway') {
                $apiMatches = TournamentCtrl::tournamentMatches($arg['id'], $this->challongeKey, $this->challongeApi);
                foreach ($apiMatches as $key => $match) {
                    $matches[$key] = $match->match;
                    $matches[$key]->player1 = $teams[$matches[$key]->player1_id];
                    $matches[$key]->player2 = $teams[$matches[$key]->player2_id];
                }
            }
            return $this->view->render($res, 'tournament.html.twig', [
                'tournament' => $tournament,
                'joined' => !!$member[0],
                'api' => $api,
                'ready' => $ready,
                'user' => $user,
                'matches' => $matches
            ]);
        } catch (ModelNotFoundException $ex) {
            $resData = array(
                'message' => 'Get Tournament Error: ' . $ex->getMessage()
            );
            return $res->withJson($resData, 404); //pagina 404 da fare!
        }
    }
}