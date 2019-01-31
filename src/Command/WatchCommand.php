<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\InvalidScriptException;
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
    protected static $defaultName = 'scripts:watch';

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
         * @var Ai[]
         */
        $ais = Collection::from($ais)
            ->indexBy(function (Ai $ai) {
                return "{$this->scriptsDir}{$ai->getPath()}";
            })
            ->toArray();

        $this->watcher->watch()->onModify(function (ResourceInterface $resource, string $path) use ($io, $ais) {
            $ai = $ais[$path];

            try {
                $ais[$path] = $this->aiApi->saveAi($ai, file_get_contents($path))->wait();
                $io->text("<info>{$ai->getPath()}</info> Successfuly synced !");
            } catch (InvalidScriptException $exception) {
                $io->text("<error>{$ai->getPath()}</error> {$exception->getPosition()} {$exception->getError()}");
            }
        });

        $io->section("Starting watch on $this->scriptsDir");
        $this->watcher->start();
    }
}
