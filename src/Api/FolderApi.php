<?php

namespace App\Api;

use App\Model\Folder;
use App\Response\PostAiFolderDeleteResponse;
use App\Response\PostAiFolderRenameResponse;
use App\Response\PostFolderChangeFolderResponse;
use App\Response\PostFolderNewResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class FolderApi
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

    public function createFolder(Folder $folder)
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'folder_id' => $folder->getFolder()->getId(),
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai-folder/new/', [
            'body' => http_build_query($data),
            'class' => PostFolderNewResponse::class,
        ])
            ->then(function (PostFolderNewResponse $response) use ($folder) {
                $folder->setId($response->getId());

                return $folder;
            });
    }

    public function changeFolder(Folder $folderToMove, Folder $destinationFolder)
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'folder_id' => $folderToMove->getId(),
            'dest_folder_id' => $destinationFolder->getId(),
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai-folder/change-folder/', [
            'body' => http_build_query($data),
            'class' => PostFolderChangeFolderResponse::class,
        ])
            ->then(function (PostFolderChangeFolderResponse $response) use ($destinationFolder, $folderToMove) {
                $folderToMove->getFolder()->removeFolder($folderToMove);
                $destinationFolder->addFolder($folderToMove);

                return $folderToMove;
            });
    }

    public function deleteFolder(Folder $folder): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'folder_id' => $folder->getId(),
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai-folder/delete/', [
            'body' => http_build_query($data),
            'class' => PostAiFolderDeleteResponse::class,
        ]);
    }

    public function renameFolder(Folder $folder, string $name): PromiseInterface
    {
        $token = $this->tokenStorage->getToken();

        $data = [
            'folder_id' => $folder->getId(),
            'new_name' => $name,
            'token' => $token,
        ];

        return $this->client->postAsync('/api/ai-folder/rename/', [
            'body' => http_build_query($data),
            'class' => PostAiFolderRenameResponse::class,
        ])
            ->then(function (PostAiFolderRenameResponse $response) use ($name, $folder) {
                $folder->setName($name);

                return $folder;
            });
    }
}
