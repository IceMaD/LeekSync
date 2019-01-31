<?php

namespace App\Api;

use App\Model\Ai;
use App\Model\Folder;
use App\Response\GetAiResponse;
use App\Response\GetFarmerAisResponse;
use App\Response\PostAiChangeFolderResponse;
use App\Response\PostAiDeleteResponse;
use App\Response\PostAiNewResponse;
use App\Response\PostAiRenameResponse;
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

    public function createAi(Ai $ai)
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'folder_id' => $ai->getFolder()->getId(),
            'v2' => 'false',
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai/new/', [
            'body' => http_build_query($data),
            'class' => PostAiNewResponse::class,
        ])
            ->then(function (PostAiNewResponse $response) use ($ai) {
                $ai->setId($response->getAi()->getId());

                return $ai;
            });
    }

    public function updateAiCode(Ai $ai, string $code): PromiseInterface
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

    public function renameAi(Ai $ai, string $name): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'ai_id' => $ai->getId(),
            'new_name' => $name,
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai/rename/', [
            'body' => http_build_query($data),
            'class' => PostAiRenameResponse::class,
        ])
            ->then(function (PostAiRenameResponse $response) use ($name, $ai) {
                $ai->setName($name);

                return $ai;
            });
    }

    public function deleteAi(Ai $ai): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'ai_id' => $ai->getId(),
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai/delete/', [
            'body' => http_build_query($data),
            'class' => PostAiDeleteResponse::class,
        ])
            ->then(function () use ($ai) {
                $ai->getFolder()->removeAi($ai);
            });
    }

    public function changeFolder(Ai $ai, Folder $folder)
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'ai_id' => $ai->getId(),
            'folder_id' => $folder->getId(),
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai/change-folder/', [
            'body' => http_build_query($data),
            'class' => PostAiChangeFolderResponse::class,
        ])
            ->then(function (PostAiChangeFolderResponse $response) use ($ai, $folder) {
                $ai->getFolder()->removeAi($ai);
                $folder->addAi($ai);

                return $ai;
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
