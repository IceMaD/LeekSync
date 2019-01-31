<?php

namespace App\Api;

use App\Model\Ai;
use App\Model\Folder;
use App\Response\GetAiResponse;
use App\Response\GetFarmerAisResponse;
use App\Response\PostAiSaveResponse;
use DusanKasan\Knapsack\Collection;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AiApi
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(Client $client, TokenStorage $tokenStorage, SerializerInterface $serializer)
    {
        $this->client = $client;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
    }

    public function getFarmerAIs(): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        return $this->client->getAsync("/api/ai/get-farmer-ais/$token", ['class' => GetFarmerAisResponse::class]);
    }

    public function getAI(int $id): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        return $this->client->getAsync("/api/ai/get/$id/$token", ['class' => GetAiResponse::class])
            ->then(function (GetAiResponse $response) {
                return $response->getAi();
            });
    }

    public function saveAi(Ai $ai, string $code): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'ai_id' => $ai->getId(),
            'code' => $code,
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai/save/', [
            'body' => http_build_query($data),
            'class' => PostAiSaveResponse::class,
        ])
            ->then(function (PostAiSaveResponse $response) use ($code, $ai) {
                if ($response->isAiValid()) {
                    $ai->setCode($code);

                    return $ai;
                }

                ['line' => $line, 'column' => $column, 'error' => $error] = $response->getError();

                throw new InvalidScriptException($line, $column, $error);
            });
    }

    public function getTree(Folder $root)
    {
        $root->setAis(
            Collection::from($root->getAis())
                ->map(function (Ai $ai) {
                    $ai->setCode($this->getAI($ai->getId())->wait()->getCode());

                    return $ai;
                })
                ->toArray()
        );

        $root->setFolders(
            Collection::from($root->getFolders())
                ->map(function (Folder $folder) {
                    return $this->getTree($folder);
                })
                ->toArray()
        );

        return $root;
    }
}
