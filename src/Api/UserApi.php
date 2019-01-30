<?php

namespace App\Api;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;

class UserApi
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function login($login, $password): PromiseInterface
    {
        $data = [
            'login' => $login,
            'password' => $password,
        ];

        return $this->client->postAsync("/api/farmer/login-token/", ['body' => http_build_query($data)])
            ->then(function (Response $response) {
                // @TODO deserialize
                return json_decode($response->getBody()->getContents());
            });
    }
}
