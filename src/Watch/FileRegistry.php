<?php

namespace App\Watch;

use App\Model\Ai;
use App\Model\Folder;
use App\TreeManagement\Builder;
use DusanKasan\Knapsack\Collection;

class FileRegistry
{
    /**
     * @var Collection
     */
    private $ais;

    /**
     * @var Collection
     */
    private $folders;

    /**
     * @var string
     */
    private $scriptsDir;

    /**
     * @var string
     */
    private $extension;

    public function __construct(string $scriptsDir)
    {
        $this->scriptsDir = $scriptsDir;
        $this->extension = getenv('APP_FILE_EXTENSION');
        $this->ais = Collection::from([]);
        $this->folders = Collection::from([]);
    }

    public function init(Folder $tree)
    {
        $this->ais = Collection::from(Builder::flattenAis($tree))
            ->indexBy(function (Ai $ai) {
                return $this->getAiPath($ai);
            });

        $this->folders = Collection::from(Builder::flattenFolders($tree))
            ->indexBy(function (Folder $folder) {
                return $this->getFolderPath($folder);
            });
    }

    public function fetchAi(string $path): Ai
    {
        return $this->ais->get($path);
    }

    public function pushAi(Ai $ai)
    {
        $this->ais->append($ai, $this->getAiPath($ai));
    }

    public function deleteAi(Ai $ai)
    {
        $this->ais->except([$this->getAiPath($ai)]);
    }

    public function moveAi(Ai $ai, string $fromPath)
    {
        $toPath = $this->getAiPath($ai);

        $this->ais
            ->mapcat(function (Ai $ai, $path) use ($fromPath, $toPath) {
                $path = $path === $fromPath ? $toPath : $path;

                return [$path => $ai];
            });
    }

    public function fetchFolder(string $path): Folder
    {
        return $this->folders->get($path);
    }

    public function pushFolder(Folder $folder)
    {
        $this->folders->append($folder, $this->getFolderPath($folder));
    }

    public function deleteFolder(Folder $folder)
    {
        $this->ais->except([$this->getFolderPath($folder)]);
    }

    public function moveFolder(Folder $folder, string $fromPath)
    {
        $toPath = $this->getFolderPath($folder);

        $this->ais
            ->mapcat(function (Folder $folder, $path) use ($fromPath, $toPath) {
                $path = $path === $fromPath ? $toPath : $path;

                return [$path => $folder];
            });
    }

    private function getAiPath(Ai $ai): string
    {
        return "{$this->scriptsDir}{$ai->getPath()}.{$this->extension}";
    }

    private function getFolderPath(Folder $folder): string
    {
        return "{$this->scriptsDir}{$folder->getPath()}";
    }
}
