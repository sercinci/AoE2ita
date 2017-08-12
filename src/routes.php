<?php
// Routes
use Controller\Steam\SteamCtrl;
use Controller\View\ViewCtrl;

//=========================================================== REGISTRATION ROUTES =====/

/**
 * GET /registration/steam
 */
$app->get('/registration/steam', 
    SteamCtrl::class . ':steamLogin')->setName('registration');

/**
 * GET /registration/success
 */
$app->get('/registration/success', 
    SteamCtrl::class . ':steamSuccess');

/**
 * GET /logout
 */
$app->get('/logout', 
    SteamCtrl::class . ':logout')->setName('logout');

//=========================================================== VIEWS ROUTES =====/

/**
 * GET /
 */
$app->get('/', 
    ViewCtrl::class . ':showIndex')->setName('index');

/**
 * GET /login
 */
$app->get('/login', 
    ViewCtrl::class . ':showLogin')->setName('login');

/**
 * GET /{slug}
 */
$app->get('/{slug}', 
    ViewCtrl::class . ':showUser')->setName('user');
