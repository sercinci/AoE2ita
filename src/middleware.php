<?php
// Application middleware

$app->add(new \Slim\Middleware\JwtAuthentication([
    "path" => ["/"],
    "passthrough" => ["/registration", "/login"],
    "secure" => false,
    "secret" => $app->getContainer()['settings']['key'],
    "logger" => $app->getContainer()['logger'],
    "callback" => function($req, $res, $arg) use ($container) {
        $container["jwt"] = $arg["decoded"];
    },
    "error" => function($req, $res, $arg) {
        return $res->withRedirect('/login', 401);
        //return $res->withJson(array('message' => 'Token expired'));
    },
    /*"rules" => [
        new \Slim\Middleware\JwtAuthentication\RequestPathRule([
            "path" => ["/"],
            "passthrough" => []
        ]),
        new \Slim\Middleware\JwtAuthentication\RequestMethodRule([
            "passthrough" => ["GET"]
        ])
    ]*/
]));