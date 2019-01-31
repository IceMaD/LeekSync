<?php

namespace App\Api;

use App\Response\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class Client extends \GuzzleHttp\Client
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(array $config = [], SerializerInterface $serializer)
    {
        $config['base_uri'] = 'https://leekwars.com:443/api';
        $config['headers'] = [
            'User-Agent' => 'Guzzle LeekTools',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];

        parent::__construct($config);
        $this->serializer = $serializer;
    }

    public function requestAsync($method, $uri = '', array $options = [])
    {
        if (!isset($options['class'])) {
            throw new \Exception('Missing class option in API request');
        }

        if (!is_subclass_of($options['class'], Response::class)) {
            throw new \Exception('class option should be a sublcass of '.Response::class);
        }

        return parent::requestAsync($method, $uri, $options)
            ->then(function (ResponseInterface $response) use ($options) {
                return $this->serializer->deserialize($response->getBody()->getContents(), $options['class'], 'json');
            })
            ->then(function (Response $response) {
                if (!$response->isSuccess()) {
                    throw new RequestFailedException($response);
                }

                return $response;
            });
    }
}
