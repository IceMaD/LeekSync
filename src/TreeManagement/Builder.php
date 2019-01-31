<?php

namespace App\TreeManagement;

use App\Response\GetFarmerAisResponse;
use App\Model\Folder;
use DusanKasan\Knapsack\Collection;

class Builder
{
    public static function buildFolderTree(GetFarmerAisResponse $farmer)
    {
        /**
         * @var Folder[]
         */
        $folders = Collection::from($farmer->getFolders())
            ->append((new Folder())->setId(0)->setName('root'))
            ->indexBy(function (Folder $folder) {
                return $folder->getId();
            })
            ->toArray();

        foreach ($farmer->getAis() as $ai) {
            $folders[$ai->getFolder()]->addAi($ai);
        }

        foreach ($folders as $folder) {
            $id = $folder->getFolder();

            if (null === $id) {
                continue;
            }

            $folders[$id]->addFolder($folder);
        }

        return $folders[0];
    }

    public static function flattenTree(Folder $tree)
    {
        $ais = [];

        foreach ($tree->getAis() as $ai) {
            $ais[] = $ai;
        }

        foreach ($tree->getFolders() as $folder) {
            $ais = array_merge($ais, self::flattenTree($folder));
        }

        return $ais;
    }
}
