<?php

namespace App\Api;

class Client extends \GuzzleHttp\Client
{
    public function __construct(array $config = [])
    {
        $config['base_uri'] = 'https://leekwars.com:443/api';
        $config['headers'] = [
            "User-Agent" => "Guzzle LeekSync",
            "Content-Type" => "application/x-www-form-urlencoded",
            "Accept" => "application/json",
        ];

        parent::__construct($config);
    }
}
