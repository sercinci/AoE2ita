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

        /* Torneo FeL */
        $fel = Tournament::with(['teams' => function($query) {
                    $query->with(['members' => function($query) {
                        $query->select('id', 'username', 'slug', 'avatar', 'profileurl', 'mmr_dm', 'mmr_rm', 'games');
                    }]);
                    $query->withCount('members');
                }])
                ->withCount('teams')
                ->where('status', 'fel')
                ->get();
        foreach ($fel as $f) {
            $f->joined = 0;
            $f->subscribed = false;
            foreach ($user->teams as $t) {
                if ($t->tournament_id == $f->id) {
                    $f->subscribed = true;
                    break;
                }
            }
            foreach ($f->teams as $team) {
                $f->joined += $team->members_count;
            }
        }
        /* fine torneo FeL */

        return $this->view->render($res, 'tournaments.html.twig', [
            'user' => $user,
            'tournaments' => $tournaments,
            'fel' => $fel[0]
        ]);
    }

    private function getUserTournaments()
    {
        $user = User::with(['teams' => function($query) {
                $query->with(['tournament' => function($query) {
                    $query->where('status', 'underway')
                        ->orWhere('status', 'complete')
                        ->select('id', 'title', 'status')
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
        $user = $this->getUserTournaments();
        $member = User::whereHas('teams', function ($query) use ($arg) {
                $query->where('tournament_id', $arg['id']);
            })
            ->with(['teams' => function ($query) use ($arg) {
                $query->where('tournament_id', $arg['id']);
            }])
            ->where('id', $this->userData->id)
            ->get();
        
        try {
            $tournament = Tournament::with(['teams' => function($query) {
                    $query->with('members');
                    $query->withCount('members');
                }])
                ->findOrFail($arg['id']);
            $api = TournamentCtrl::tournamentDetail($arg['id'], $this->challongeKey, $this->challongeApi); //forse non serve
            $ready = true;
            $teams = array();
            foreach ($tournament->teams as $team) {
                $tournament->joined += $team->members_count;
                if ($team->members_count < $tournament->team_members) {
                    $ready = false;
                    //break;
                }
                $teams[$team->id] = $team;
            }
            $matches = array();
            $myMatch = null;
            $myTeam = null;
            $opponentTeam = null;
            if ($tournament->status == 'underway' || $tournament->status == 'complete') {
                $apiMatches = TournamentCtrl::tournamentMatches($arg['id'], $this->challongeKey, $this->challongeApi);
                foreach ($apiMatches as $key => $match) {
                    $matches[$key] = $match->match;
                    $matches[$key]->player1 = $teams[$matches[$key]->player1_id];
                    $matches[$key]->player2 = $teams[$matches[$key]->player2_id];
                    if (($matches[$key]->player1_id == $member[0]->teams[0]->id || $matches[$key]->player2_id == $member[0]->teams[0]->id) && $matches[$key]->state == 'open') {
                        $myMatch = $matches[$key];
                        if ($matches[$key]->player1_id == $member[0]->teams[0]->id) {
                            $myTeam = $matches[$key]->player1_id;
                            $opponentTeam = $matches[$key]->player2_id;
                        } else {
                            $myTeam = $matches[$key]->player2_id;
                            $opponentTeam = $matches[$key]->player1_id;
                        }
                    }
                }
            }
            /* fel */
            if ($tournament->status == 'fel') {
                return $this->view->render($res, 'tournamentFel.html.twig', [
                    'tournament' => $tournament,
                    'joined' => !!$member[0],
                    'api' => $api,
                    'ready' => $ready,
                    'user' => $user,
                    'matches' => $matches,
                    'myMatch' => $myMatch,
                    'myTeam' => $myTeam,
                    'opponentTeam' => $opponentTeam
                ]);
            }
            return $this->view->render($res, 'tournament.html.twig', [
                'tournament' => $tournament,
                'joined' => !!$member[0],
                'api' => $api,
                'ready' => $ready,
                'user' => $user,
                'matches' => $matches,
                'myMatch' => $myMatch,
                'myTeam' => $myTeam,
                'opponentTeam' => $opponentTeam
            ]);
        } catch (ModelNotFoundException $ex) {
            $resData = array(
                'message' => 'Get Tournament Error: ' . $ex->getMessage()
            );
            return $res->withJson($resData, 404); //pagina 404 da fare!
        }
    }

    public function showFbTournament($req, $res, $arg)
    {
        if ($arg['id'] == 'torneo-fel') {
            $tournament = Tournament::where('status', 'fel')->get();
            return $this->view->render($res, 'social_tournament.html.twig', [
                'tournament' => $tournament
            ]);
        }
        $tournament = Tournament::find($arg['id']);
        return $this->view->render($res, 'social_tournament.html.twig', [
            'tournament' => $tournament
        ]);
    }

    public function showFbNewTournament($req, $res, $arg)
    {
        $title = 'Crea torneo';
        $description = 'Crea il tuo torneo stabilendo numero di giocatori, squadre, classifica e regolamento.';
        $path = 'tournaments/new';
        return $this->view->render($res, 'social.html.twig', [
            'title' => $title,
            'description' => $description,
            'path' => $path
        ]);
    }

    public function showFbTournaments($req, $res, $arg)
    {
        $title = 'Tornei';
        $description = 'Cerca un torneo a cui partecipare o controlla il progresso di quelli in cui stai giocando.';
        $path = 'tournaments';
        return $this->view->render($res, 'social.html.twig', [
            'title' => $title,
            'description' => $description,
            'path' => $path
        ]);
    }

    /*public function showFelTournament($req, $res, $arg)
    {
        $user = $this->getUserTournaments();
        $tournament = Tournament::where('status', 'fel')->get();
        $tId = $tournament[0]->id;
        $member = User::whereHas('teams', function ($query) use ($tId) {
                $query->where('tournament_id', $tId);
            })
            ->with(['teams' => function ($query) use ($tId) {
                $query->where('tournament_id', $tId);
            }])
            ->where('id', $this->userData->id)
            ->get();
        
        return $this->view->render($res, 'tournamentFel.html.twig', [
            'tournament' => $tournament[0],
            'joined' => !!$member[0],
            'user' => $user
        ]);
    }*/
}