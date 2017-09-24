<?php
// Routes
use Controller\Steam\SteamCtrl;
use Controller\View\ViewCtrl;
use Controller\Tournament\TournamentCtrl;
use Controller\Services\FeedbackCtrl;

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

//=========================================================== STEAM ROUTES =====/

/**
 * GET /steam/status/{id}
 */
$app->get('/steam/status/{id}', 
    SteamCtrl::class . ':steamStatus');

//=========================================================== TOURNAMENTS ROUTES =====/

/**
 * POST /tournaments/new
 */
$app->post('/tournaments/new', 
    TournamentCtrl::class . ':create');

/**
 * PUT /tournaments/{id}/team/{teamid}
 */
$app->put('/tournaments/{id}/team/{teamid}', 
    TournamentCtrl::class . ':joinTeam');

/**
 * PUT /tournaments/{id}/randomteam
 */
$app->put('/tournaments/{id}/randomteam', 
    TournamentCtrl::class . ':joinRandomTeam');

/**
 * POST /tournaments/leave/{id}/{mmr}
 */
$app->post('/tournaments/leave/{id}/{mmr}', 
    TournamentCtrl::class . ':leaveTeam');

/**
 * POST /tournaments/{id}/start
 */
$app->post('/tournaments/{id}/start', 
    TournamentCtrl::class . ':startTournament');

/**
 * POST /tournaments/{id}/match/{match_id}
 */
$app->post('/tournaments/{id}/match/{match_id}', 
    TournamentCtrl::class . ':matchScore');

/**
 * DELETE /tournaments/{id}/start
 */
$app->delete('/tournaments/{id}/delete', 
    TournamentCtrl::class . ':deleteTournament');

/**
 * POST /tournaments/{id}/close
 */
$app->post('/tournaments/{id}/close', 
    TournamentCtrl::class . ':closeTournament');

/**
 * DELETE /tournaments/crondelete
 */
$app->delete('/tournaments/crondelete', 
    TournamentCtrl::class . ':deleteCompleteTournaments');

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
 * GET /tournaments
 */
$app->get('/tournaments', 
    ViewCtrl::class . ':showTournaments')->setName('tournaments');

/**
 * GET /{slug}
 */
/*$app->get('/{slug}', 
    ViewCtrl::class . ':showUser')->setName('user');*/

/**
 * GET /tournaments/new
 */
$app->get('/tournaments/new', 
    ViewCtrl::class . ':showNewTournament')->setName('new_tournament');

/**
 * GET /tournaments/{id}
 */
$app->get('/tournaments/{id}', 
    ViewCtrl::class . ':showTournament')->setName('tournament');

//=========================================================== SERVICES ROUTES =====/

/**
 * POST /feedback
 */
$app->post('/feedback', 
    FeedbackCtrl::class . ':feedbackText');

//== SOCIAL PRIVATE ROUTES

/**
 * GET /fb/tournaments
 */
$app->get('/fb/tournaments', 
    ViewCtrl::class . ':showFbTournaments');

/**
 * GET /tournaments/new
 */
$app->get('/fb/tournaments/new', 
    ViewCtrl::class . ':showFbNewTournament');

/**
 * GET /tournaments/{id}
 */
$app->get('/fb/tournaments/{id}', 
    ViewCtrl::class . ':showFbTournament');
