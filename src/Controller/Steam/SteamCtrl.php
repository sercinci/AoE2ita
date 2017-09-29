<?php
namespace Controller\Steam;

use Controller\Steam\BaseCtrl;
use Entity\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Hybridauth\Provider\Steam;
use Hybridauth\Hybridauth;
use Ramsey\Uuid\Uuid;
//use Cviebrock\EloquentSluggable\Services\SlugService;

/**
* Manage platform steam login and APIs
*/
class SteamCtrl extends BaseCtrl
{
    public function steamLogin($req, $res, $arg)
    {
        try {
            $adapter = new Steam($this->hybridConfig['Steam']);
            $adapter->authenticate();
            $user_profile = $adapter->getUserProfile();
            if (empty($user_profile)) {
                $this->logger->error('Steam authentication error');
                return $res->withStatus(404);
            }
            try {
                $user = User::where('steam_id', $user_profile->identifier)
                    ->firstOrFail();
                $this->logger->info('Steam login successful: ' . $user->username . ' - ' . $user->email);
                $res = $this->setTokenCookie($res, $user);
                return $this->view->render($res, 'success.html.twig', [
                    'username' => $user->username
                ]);
            } catch (ModelNotFoundException $ex) {
                $user = $this->signupSocial($user_profile);
                if ($user) {
                    $res = $this->setTokenCookie($res, $user);
                    return $this->view->render($res, 'success.html.twig', [
                    'username' => $user->username
                ]);
                } else {
                    return $res->withStatus(500);
                }
            }
        } catch (Hybridauth\Exception\HttpClientFailureException $e) {
            $this->logger->error('Curl text error message : ' . $adapter->getHttpClient()->getResponseClientError());
            return $res->withStatus(500);
        } catch (Exception $e) {
            $this->logger->info('Other Steam Login Exception: ' . $e->getMessage());
            return $res->withStatus(500);
        }
    }

    public function steamSuccess($req, $res, $arg)
    {
        try {
            $adapter = new Steam($this->hybridConfig['Steam']);
            $adapter->authenticate();
            $user_profile = $adapter->getUserProfile();
            if (empty($user_profile)) {
                $this->logger->error('Steam authentication error');
                return $res->withStatus(500);
            }
            try {
                $user = User::where('steam_id', $user_profile->identifier)
                    ->firstOrFail();
                $this->logger->info('Steam login successful: ' . $user->username . ' - ' . $user->email);
                $res = $this->setTokenCookie($res, $user);
                return $this->view->render($res, 'success.html.twig', [
                    'username' => $user->username
                ]);                
            } catch (ModelNotFoundException $ex) {
                $user = $this->signupSocial($user_profile);
                if ($user) {
                    $res = $this->setTokenCookie($res, $user);
                    return $this->view->render($res, 'success.html.twig', [
                    'username' => $user->username
                ]);
                } else {
                    return $res->withStatus(500);
                }
            }
        } catch (Hybridauth\Exception\HttpClientFailureException $e) {
            $this->logger->error('Curl text error message : ' . $adapter->getHttpClient()->getResponseClientError());
            return $res->withStatus(500);
        } catch (Exception $e) {
            $this->logger->info('Other Steam Login Exception: ' . $e->getMessage());
            return $res->withStatus(500);
        }
    }

    public function logout($req, $res, $arg)
    {
        $hybridauth = new Hybridauth();
        $hybridauth->disconnectAllAdapters(); //to test!
        $res = $this->removeTokenCookie($res);
        return $res->withRedirect('/', 200);
    }

    private function signupSocial($profile)
    {
        $user = new User;
        $user->id = Uuid::uuid4();
        $user->username = $profile->displayName;
        $user->profileurl = $profile->profileURL;
        //$user->slug = SlugService::createSlug(User::class, 'slug', $profile->displayName);
        $user->avatar = $profile->photoURL;
        $user->steam_id = $profile->identifier;
        $stats = $this->stats($user->steam_id, $this->hybridConfig['Steam']['keys']['secret']);
        $user->mmr_dm = $stats['mmr_dm'] ? $stats['mmr_dm'] : 1600;
        $user->mmr_rm = $stats['mmr_rm'] ? $stats['mmr_rm'] : 1600;
        $user->games = $stats['games'] ? $stats['games'] : 0;
        if ($user->save()) {
            $this->logger->info('Social signup successful: ' . $user->username . ' - ' . $user->email);
            return $user;
        } else {
            $this->logger->error('Database User Save Error');
            return false;
        }
    }

    public function stats($steam_id, $secret)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'http://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid=221380&key=' . $secret . '&steamid=' . $steam_id
        ));
        $resp = json_decode(curl_exec($curl));
        curl_close($curl);
        $stats = array();
        foreach ($resp->playerstats->stats as $value) {
            if ($value->name == 'STAT_ELO_DM') {
                $stats['mmr_dm'] = $value->value;
            }
            if ($value->name == 'STAT_ELO_RM') {
                $stats['mmr_rm'] = $value->value;
            }
            if ($value->name == 'STAT_GAMES_PLAYED_ONLINE') {
                $stats['games'] = $value->value;
            }
        }
        return $stats;
    }

    public function steamStatus($req, $res, $arg)
    {
        $user = User::where('steam_id', $arg['id'])->first();
        if ($user->updated_at < date('Y-m-d H:i:s',time()-150)) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->hybridConfig['Steam']['keys']['secret'] . '&steamids=' . $arg['id']
            ));
            $resp = json_decode(curl_exec($curl));
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $this->logger->error($err);
                return $res->withStatus(500);
            }
            $user->steam_state = $resp->response->players[0]->personastate;
            $user->save;
            $this->logger->info('State requested: ' . $user->steam_state);
        }
        return $res->withJson($user->steam_state);
    }
}