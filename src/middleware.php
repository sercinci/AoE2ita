<?php
// Application middleware

$app->add(new \Slim\Middleware\JwtAuthentication([
    "path" => ["/tournaments", "/steam", "/feedback"],
    "passthrough" => [],
    "secure" => false,
    "secret" => $app->getContainer()['settings']['key'],
    "logger" => $app->getContainer()['logger'],
    "callback" => function($req, $res, $arg) use ($container) {
        $container["jwt"] = $arg["decoded"];
    },
    "error" => function($req, $res, $arg) {
        if(strpos($_SERVER["HTTP_USER_AGENT"], "facebookexternalhit/1.1") !== false || 
            strpos($_SERVER["HTTP_USER_AGENT"], "Facebot") !== false || 
            strpos($_SERVER["HTTP_USER_AGENT"], "Googlebot") !== false || 
            strpos($_SERVER["HTTP_USER_AGENT"], "Bingbot") !== false || 
            strpos($_SERVER["HTTP_USER_AGENT"], "Twitterbot") !== false) {
            return $res->withRedirect('/fb' . $req->getUri()->getPath(), 200);
        } else {
            return $res->withRedirect('/?auth=false', 401);
        }
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