<?php
namespace Controller\Steam;

use Firebase\JWT\JWT;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigResponseCookies;

/**
 * Steam base class
 */
abstract class BaseCtrl
{
    public function __construct($c)
    {
        $this->logger = $c->get('logger');
        $this->users = $c->get('db')->table('users');
        $this->key = $c->get('settings')['key'];
        $this->serverName = $c->get('settings')['server'];
        $this->hybridConfig = $c->get('settings')['hybridauth'];
        $this->baseName = $c->get('settings')['base'];
        $this->view = $c->get('view');
    }

    protected function setTokenCookie($response, $user)
    {
        return FigResponseCookies::set($response, SetCookie::create('token')
            ->withValue($this->createToken($user))
            //->withDomain($this->baseName)
            ->withPath('/')
            ->rememberForever()
            ->withSecure(false)
            ->withHttpOnly(true)
            ->withSameSite('Lax')
        );
    }

    protected function removeTokenCookie($response)
    {
        return FigResponseCookies::expire($response, 'token');
    }

    private function createToken($user)
    {
        $userData = array(
            'id' => $user->id
            //'steam_id' => $user->steam_id
        ); //solo dati da tenere in token
        
        $tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $notBefore  = $issuedAt + 0;             // Adding 0 seconds
        $expire     = $notBefore + 86400;        // Adding 1 day
        $serverName = $this->serverName; // Retrieve the server name from config file
        
        /*
         * Create the token as an array
         */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            'data' => $userData          // User data
        ];
        //$secretKey = base64_decode($this->key); //getEnv() utilizzato per prendere variabili di sistema, da registrare sul server!
        $secretKey = $this->key;
        $jwt = JWT::encode(
            $data,      // Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
        
        return $jwt;
    }
}