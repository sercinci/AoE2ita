<?php
namespace Controller\Services;

use Maknz\Slack\Client;

/**
 * Channel base class
 */
abstract class BaseCtrl
{
    public function __construct($c)
    {
        $this->logger = $c->get('logger');
        $this->userData = $c->get('jwt') ? $c->get('jwt')->data : null;
        $this->slackHook = $c->get('settings')['slackHook'];
        $this->users = $c->get('db')->table('users');
    }

    protected function slack($settings = [])
    {
        return new Client($this->slackHook, $settings);
    }
}