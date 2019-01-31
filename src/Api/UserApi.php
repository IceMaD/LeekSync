<?php

namespace App\Api;

use App\Response\PostLoginTokenResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserApi
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(Client $client, SerializerInterface $serializer)
    {
        $this->client = $client;
        $this->serializer = $serializer;
    }

    public function login($login, $password): PromiseInterface
    {
        $data = [
            'login' => $login,
            'password' => $password,
        ];

        return $this->client->postAsync('/api/farmer/login-token/', [
            'body' => http_build_query($data),
            'class' => PostLoginTokenResponse::class,
        ]);
    }
}
