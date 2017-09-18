<?php
namespace Controller\Services;

use Controller\Services\BaseCtrl;
use Entity\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
* Report service
*/
class FeedbackCtrl extends BaseCtrl
{
    public function feedbackText($req, $res, $arg)
    {
        $body = $req->getParsedBody();
        try {
            $user = User::find($this->userData->id);
            $settings = [
                'username' => $user->username,
                'channel' => '#feedback',
                'icon' => $user->avatar,
                'allow_markdown' => false
            ];
            $client = $this->slack($settings);
            
            $client->send($body['feedbackText'] . ' - ' . $user->profileurl); //in caso vediamo se chiedere la mail
            return $res->withStatus(200);
        } catch (ModelNotFoundException $ex) {
            $resData = array(
                'message' => 'Feedback Error: ' . $ex->getMessage()
            );
            return $res->withJson($resData, 400);
        } catch (Exception $e) { 
            $this->logger->error('Feedback Slack Error: ' . $e->getMessage());
            $resData = array(
                'message' => 'Feedback Slack Error: ' . $e->getMessage()
            );
            return $res->withJson($resData, 400);
        }
    }
}