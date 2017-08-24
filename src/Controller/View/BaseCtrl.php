<?php
namespace Controller\View;

/**
 * View base class
 */
abstract class BaseCtrl
{
    public function __construct($c)
    {
        $this->logger = $c->get('logger');
        $this->users = $c->get('db')->table('users');
        $this->userData = $c->get('jwt') ? $c->get('jwt')->data : null;
        $this->view = $c->get('view');
        $this->hybridConfig = $c->get('settings')['hybridauth'];
        $this->challongeKey = $c->get('settings')['challongeKey'];
        $this->challongeApi = $c->get('settings')['challongeApi'];
    }

    protected function checkDate($date)
    {
        return $date < date('Y-m-d H:i:s',time()-86400) ? true : false;
    }
}