<?php
namespace Controller\Tournament;

/**
 * Tournament base class
 */
abstract class BaseCtrl
{
    public function __construct($c)
    {
        $this->logger = $c->get('logger');
        $this->users = $c->get('db')->table('users');
        $this->challongeKey = $c->get('settings')['challongeKey'];
        $this->challongeApi = $c->get('settings')['challongeApi'];
        $this->view = $c->get('view');
        $this->userData = $c->get('jwt') ? $c->get('jwt')->data : null;
        $this->baseName = $c->get('settings')['base'];
    }
}