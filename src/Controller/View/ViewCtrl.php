<?php
namespace Controller\View;

use Controller\View\BaseCtrl;
use Entity\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Controller\Steam\SteamCtrl;

/**
* Template render
*/
class ViewCtrl extends BaseCtrl
{
    public function showIndex($req, $res, $arg)
    {
        $user = User::find($this->userData->id);
        if ($this->checkDate($user->updated_at)) {
            $stats = SteamCtrl::stats($user->steam_id);
            $user->mmr_dm = $stats['mmr_dm'];
            $user->mmr_rm = $stats['mmr_rm'];
            $user->save();
        }
        return $this->view->render($res, 'index.html.twig', [
            'user' => $user
        ]);
    }

    public function showLogin($req, $res, $arg)
    {
        return $this->view->render($res, 'login.html.twig');
    }

    public function showUser($req, $res, $arg)
    {
        $user = User::where('slug', $arg['slug'])->first();
        if ($user) { 
            if ($this->checkDate($user->updated_at)) {
                $stats = SteamCtrl::stats($user->steam_id);
                $user->mmr_dm = $stats['mmr_dm']; 
                $user->mmr_rm = $stats['mmr_rm']; 
                $user->save();
            }
            return $this->view->render($res, 'user.html.twig', [
                'user' => $user
            ]);
        } else {
            return $res->withStatus(404);
        }
    }
}