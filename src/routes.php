<?php
// Routes
use Controller\Steam\SteamCtrl;
use Controller\View\ViewCtrl;
use Controller\Tournament\TournamentCtrl;

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

//=========================================================== TOURNAMENTS ROUTES =====/

/**
 * POST /tournament/new
 */
$app->post('/tournaments/new', 
    TournamentCtrl::class . ':create');

/**
 * PUT /tournament/{id}/team/{teamid}
 */
$app->put('/tournaments/{id}/team/{teamid}', 
    TournamentCtrl::class . ':joinTeam');

/**
 * PUT /tournament/{id}/randomteam
 */
$app->put('/tournaments/{id}/randomteam', 
    TournamentCtrl::class . ':joinRandomTeam');

/**
 * POST /tournament/{id}/start
 */
$app->post('/tournaments/{id}/start', 
    TournamentCtrl::class . ':startTournament');

/**
 * DELETE /tournament/{id}/start
 */
$app->delete('/tournaments/{id}/delete', 
    TournamentCtrl::class . ':deleteTournament');

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
 * GET /tournament/{id}
 */
$app->get('/tournaments', 
    ViewCtrl::class . ':showTournaments')->setName('tournaments');

/**
 * GET /{slug}
 */
$app->get('/{slug}', 
    ViewCtrl::class . ':showUser')->setName('user');

/**
 * GET /tournament/new
 */
$app->get('/tournaments/new', 
    ViewCtrl::class . ':showNewTournament')->setName('new_tournament');

/**
 * GET /tournament/{id}
 */
$app->get('/tournaments/{id}', 
    ViewCtrl::class . ':showTournament')->setName('tournament');