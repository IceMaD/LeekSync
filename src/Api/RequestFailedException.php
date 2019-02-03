<?php

namespace App\Api;

use App\Response\Response;

class RequestFailedException extends \Exception
{
    /**
     * @var Response
     */
    private $response;

    public function __construct(Response $response)
    {
        parent::__construct($response->getError(), 0);

        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
