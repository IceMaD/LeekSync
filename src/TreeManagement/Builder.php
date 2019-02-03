<?php

namespace App\TreeManagement;

use DusanKasan\Knapsack\Collection;
use IceMaD\LeekWarsApiBundle\Entity\Folder;
use IceMaD\LeekWarsApiBundle\Response\GetFarmerAisResponse;

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

    public static function flattenAis(Folder $tree)
    {
        $ais = [];

        foreach ($tree->getAis() as $ai) {
            $ais[] = $ai;
        }

        foreach ($tree->getFolders() as $folder) {
            $ais = array_merge($ais, self::flattenAis($folder));
        }

        return $ais;
    }

    public static function flattenFolders(Folder $tree)
    {
        $folders = [$tree];

        foreach ($tree->getFolders() as $folder) {
            $folders = array_merge($folders, self::flattenFolders($folder));
        }

        return $folders;
    }
}
