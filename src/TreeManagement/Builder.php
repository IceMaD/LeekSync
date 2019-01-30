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
         * @var $folders Folder[]
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
            if ($folder->getFolder() === null) {
                continue;
            }

            $folders[$folder->getFolder()]->addFolder($folder);
        }

        return $folders[0];
    }
}
