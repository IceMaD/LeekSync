<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\Model\Ai;
use App\TreeManagement\Builder;
use App\Watch\Watcher;
use DusanKasan\Knapsack\Collection;
use JasonLewis\ResourceWatcher\Resource\ResourceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WatchCommand extends Command
{
    protected static $defaultName = 'app:watch';

    /**
     * @var Watcher
     */
    private $watcher;

    /**
     * @var AiApi
     */
    private $aiApi;

    /**
     * @var string
     */
    private $scriptsDir;

    public function __construct(UserApi $userApi, TokenStorage $tokenStorage, AiApi $aiApi, Watcher $watcher, string $scriptsDir)
    {
        parent::__construct($userApi, $tokenStorage);

        $this->watcher = $watcher;
        $this->aiApi = $aiApi;
        $this->scriptsDir = $scriptsDir;
    }

    protected function configure()
    {
        $this->setDescription('Watches for files changes to push them on LeekWars.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $tree = Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait());
        $ais = Builder::flattenTree($tree);

        /**
         * @var $ais Ai[]
         */
        $ais = Collection::from($ais)
            ->indexBy(function (Ai $ai) {
                return "{$this->scriptsDir}{$ai->getPath()}";
            })
            ->toArray();

        $this->watcher->watch()->onModify(function (ResourceInterface $resource, string $path) use ($io, $ais) {
            $ai = $ais[$path];
            $io->text("<info>{$ai->getPath()}</info> changed");
        });

        $io->section("Starting watch on $this->scriptsDir");
        $this->watcher->start();
    }
}