<?php

namespace App\Command;

use App\TreeManagement\Builder;
use App\Watch\FileRegistry;
use DusanKasan\Knapsack\Collection;
use IceMaD\LeekWarsApiBundle\Api\AiApi;
use IceMaD\LeekWarsApiBundle\Api\FarmerApi;
use IceMaD\LeekWarsApiBundle\Entity\Ai;
use IceMaD\LeekWarsApiBundle\Entity\Folder;
use IceMaD\LeekWarsApiBundle\Storage\TokenStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class ScriptsCommand extends LoggedCommand
{
    /**
     * @var AiApi
     */
    protected $aiApi;

    /**
     * @var FileRegistry
     */
    protected $fileRegistry;

    public function __construct(AiApi $aiApi, FarmerApi $farmerApi, FileRegistry $fileRegistry, TokenStorage $tokenStorage)
    {
        parent::__construct($farmerApi, $tokenStorage);

        $this->aiApi = $aiApi;
        $this->farmerApi = $farmerApi;
        $this->fileRegistry = $fileRegistry;
        $this->tokenStorage = $tokenStorage;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $root = Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait());

        $this->fileRegistry->init($this->getTree($root));
    }

    protected function logIfVerbose(string $string)
    {
        if ($this->io->isVerbose()) {
            $this->io->text("<info>$string</info>");
        }
    }

    public function getTree(Folder $root)
    {
        $root->setAis(
            Collection::from($root->getAis())
                ->map(function (Ai $ai) {
                    $ai->setCode($this->aiApi->getAI($ai->getId())->wait()->getAi()->getCode());

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
