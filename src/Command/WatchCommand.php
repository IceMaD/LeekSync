<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\FolderApi;
use App\Api\InvalidScriptException;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\Model\Ai;
use App\Model\Folder;
use App\TreeManagement\Builder;
use App\TreeManagement\Dumper;
use App\Async\Deferred;
use App\Async\Pool;
use App\Watch\FileRegistry;
use App\Watch\Watcher;
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

    /**
     * @var string
     */
    private $extension;

    /**
     * @var Pool
     */
    private $deletionsPool;

    /**
     * @var string[]
     */
    private $scheduledDeletions;

    /**
     * @var Pool
     */
    private $updatesPool;

    /**
     * @var string[]
     */
    private $scheduledUpdates;

    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var FolderApi
     */
    private $folderApi;

    /**
     * @var FileRegistry
     */
    private $registry;

    public function __construct(
        UserApi $userApi,
        TokenStorage $tokenStorage,
        AiApi $aiApi,
        FolderApi $folderApi,
        Dumper $dumper,
        FileRegistry $registry,
        Watcher $watcher,
        string $scriptsDir
    ) {
        parent::__construct($userApi, $tokenStorage);

        $this->watcher = $watcher;
        $this->aiApi = $aiApi;
        $this->scriptsDir = $scriptsDir;
        $this->extension = getenv('APP_FILE_EXTENSION');
        $this->dumper = $dumper;
        $this->folderApi = $folderApi;
        $this->registry = $registry;
        // @TODO Fix Pool design problem. Lib can not have multiple pool
        $this->deletionsPool = Pool::create();
        $this->updatesPool = Pool::create();
    }

    protected function configure()
    {
        $this->setDescription('Watches for files changes to push them on LeekWars.com');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->registry->init(Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait()));

        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $listener = $this->watcher->watch();

        $listener->onDelete(function (/* @noinspection PhpUnusedParameterInspection */ ResourceInterface $resource, string $path) {
            if ($this->deletionsPool->isRunning()) {
                $this->logIfVerbose("Reschedule deletion - $path");
                $this->deletionsPool->stop();

                $this->scheduleAiDeletion($path);
            } else {
                $this->logIfVerbose("Schedule deletion - $path");
                $this->scheduleAiDeletion($path);
            }
        });

        $listener->onCreate(function (/* @noinspection PhpUnusedParameterInspection */ ResourceInterface $resource, string $path) {
            if ($this->updatesPool->isRunning()) {
                $this->updatesPool->stop();

                $this->logIfVerbose("Reschedule update - $path");
                $this->scheduleUnknownUpdate($path);
            } elseif ($this->deletionsPool->isRunning()) {
                $this->deletionsPool->stop();

                $this->logIfVerbose("Schedule update - $path");
                $this->scheduleUnknownUpdate($path);
            } else {
                $this->logIfVerbose("Create AI - $path");
                $this->createAi($path);
            }
        });

        $listener->onModify(function (/* @noinspection PhpUnusedParameterInspection */ ResourceInterface $resource, string $path) {
            $ai = $this->registry->fetchAi($path);

            try {
                $this->registry->moveAi($this->aiApi->updateAiCode($ai, file_get_contents($path))->wait(), $path);

                $this->io->text("<info>{$ai->getPath()}</info> Successfuly synced !");
            } catch (InvalidScriptException $exception) {
                $this->io->text("<error>{$ai->getPath()}</error> {$exception->getPosition()} {$exception->getError()}");
            }
        });

        $this->io->section("Starting watch on $this->scriptsDir");

        $this->watcher->start(100000);
    }

    private function guessPathParts(string $path)
    {
        $separator = DIRECTORY_SEPARATOR;

        if (!preg_match("/^(.*\\{$separator}([^\\{$separator}]*))\\{$separator}([^\\{$separator}\\.]*).*\$/", $path, $matches)) {
            throw new \RuntimeException("Unable to parse folder $path");
        }

        [, $path, $parent, $name] = $matches;

        return [$path, $name, $parent];
    }

    private function createAi(string $path)
    {
        [$folderPath, $name] = $this->guessPathParts($path);

        if (!$this->registry->hasFolder($folderPath)) {
            $this->createFolder($folderPath);
        }

        $ai = (new Ai())
            ->setCode(file_get_contents($path))
            ->setFolder($this->registry->fetchFolder($folderPath));

        $ai = $this->aiApi->createAi($ai)->wait();
        $ai = $this->aiApi->updateAiCode($ai, $ai->getCode())->wait();
        $ai = $this->aiApi->renameAi($ai, $name)->wait();
        $this->registry->pushAi($ai);

        $this->io->text("<info>{$ai->getPath()}</info> Successfully created !");
    }

    private function createFolder(string $path)
    {
        [$parentFolderPath, $name] = $this->guessPathParts($path);

        if (!$this->registry->hasFolder($parentFolderPath)) {
            $this->createFolder($parentFolderPath);
        }

        $folder = (new Folder())
            ->setFolder($this->registry->fetchFolder($parentFolderPath));

        $folder = $this->folderApi->createFolder($folder)->wait();
        $folder = $this->folderApi->renameFolder($folder, $name)->wait();

        $this->registry->pushFolder($folder);

        $this->io->text("<info>{$folder->getPath()}</info> Successfully created !");
    }

    private function renameAi(string $fromPath, string $path)
    {
        [, $name] = $this->guessPathParts($path);

        $ai = $this->registry->fetchAi($fromPath);

        $ai = $this->aiApi->renameAi($ai, $name)->wait();
        $this->registry->moveAi($ai, $fromPath);

        $this->io->text("<info>{$ai->getPath()}</info> Successfully renamed !");
    }

    private function renameFolder(string $fromPath, string $path)
    {
        [$folderPath, $name] = $this->extractFolderRename($fromPath, $path);

        $folder = $this->registry->fetchFolder($folderPath);

        $folder = $this->folderApi->renameFolder($folder, $name)->wait();
        $this->registry->moveFolder($folder, $fromPath);

        $this->io->text("<info>{$folder->getPath()}</info> Successfully renamed !");
    }

    private function moveAi(string $fromPath, string $path)
    {
        [$folderPath] = $this->guessPathParts($path);

        if (!$this->registry->hasFolder($folderPath)) {
            $this->createFolder($folderPath);
        }

        $ai = $this->registry->fetchAi($fromPath);
        $folder = $this->registry->fetchFolder($folderPath);

        $ai = $this->aiApi->changeFolder($ai, $folder)->wait();
        $this->registry->moveAi($ai, $fromPath);

        $this->io->text("<info>{$ai->getPath()}</info> Successfully moved !");
    }

    private function moveFolder(string $folderPath, string $destinationParentFolderPath)
    {
        $folder = $this->folderApi
            ->changeFolder($this->registry->fetchFolder($folderPath), $this->registry->fetchFolder($destinationParentFolderPath))
            ->wait();

        $this->registry->moveFolder($folder, $folderPath);

        $this->io->text("<info>{$folder->getPath()}</info> Successfully moved !");
    }

    private function scheduleAiDeletion(string $path)
    {
        $this->scheduledDeletions[] = $path;
        $this->deletionsPool = Pool::create();

        $deletion = new Deferred(function () {
            foreach ($this->scheduledDeletions as $path) {
                [$folderPath] = $this->guessPathParts($path);

                if (!file_exists($folderPath) && $this->registry->hasFolder($folderPath)) {
                    $folder = $this->registry->fetchFolder($folderPath);

                    $this->folderApi->deleteFolder($folder)->wait();
                    $this->registry->deleteFolder($folder);

                    $this->io->text("<error>{$folder->getPath()}</error> Successfully deleted !");
                }

                $ai = $this->registry->fetchAi($path);

                $this->aiApi->deleteAi($ai)->wait();
                $this->registry->deleteAi($ai);

                $this->io->text("<error>{$ai->getPath()}</error> Successfully deleted !");
            }

            $this->scheduledDeletions = [];
        });

        $this->deletionsPool->add($deletion->getProcess());
    }

    private function scheduleUnknownUpdate(string $path)
    {
        $this->scheduledUpdates[] = $path;
        $this->updatesPool = Pool::create();

        $update = new Deferred(function () {
            $deletionsCount = count($this->scheduledDeletions);
            $updatesCount = count($this->scheduledUpdates);

            if ($deletionsCount != $updatesCount) {
                throw new \Exception('Something bad happened, please report what you did on github');
            }

            $fromPath = $this->scheduledDeletions[0];
            $toPath = $this->scheduledUpdates[0];

            [$fromFolderPath,, $fromParentFolderPath] = $this->guessPathParts($fromPath);
            [$folderPath,, $toParentFolderPath] = $this->guessPathParts($toPath);

            if ($fromFolderPath === $folderPath) {
                $this->renameAi($fromPath, $toPath);
            } elseif ($this->isFolderRename($fromPath, $toPath)) {
                $this->renameFolder($fromPath, $toPath);
            } elseif ($fromParentFolderPath === $toParentFolderPath) {
                [$currentFolderPath, $destinationFolderPath] = $this->extractFolderMovement($fromPath, $toPath);

                $this->moveFolder($currentFolderPath, $destinationFolderPath);
            } else {
                foreach ($this->scheduledDeletions as $key => $fromPath) {
                    $this->moveAi($fromPath, $this->scheduledUpdates[$key]);
                }
            }

            $this->scheduledDeletions = [];
            $this->scheduledUpdates = [];
        });

        $this->updatesPool->add($update->getProcess());
    }

    private function extractFolderMovement(string $fromPath, string $path): array
    {
        $fromPathParts = explode(DIRECTORY_SEPARATOR, $fromPath);
        $reverseFromPathParts = array_reverse($fromPathParts);
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);
        $reversePathParts = array_reverse($pathParts);

        foreach ($reverseFromPathParts as $index => $fromPathPart) {
            if ($fromPathPart === $reversePathParts[$index]) {
                continue;
            }

            $currentFolderPath = implode(DIRECTORY_SEPARATOR, array_slice($fromPathParts, 0, -($index - 1)));

            break;
        }

        foreach ($reversePathParts as $index => $fromPathPart) {
            if ($fromPathPart === $reverseFromPathParts[$index]) {
                continue;
            }

            $destinationFolderPath = implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, -$index));

            break;
        }

        if (!isset($currentFolderPath) || !isset($destinationFolderPath)) {
            throw new \Exception('Could not determine folder movement');
        }

        return [$currentFolderPath, $destinationFolderPath];
    }

    private function isFolderRename(string $fromPath, string $toPath): bool
    {
        $fromPathParts = explode(DIRECTORY_SEPARATOR, $fromPath);
        $toPathParts = explode(DIRECTORY_SEPARATOR, $toPath);

        foreach ($fromPathParts as $index => $fromPathPart) {
            if ($fromPathPart === $toPathParts[$index]) {
                continue;
            }

            $fromPathParts = array_slice($fromPathParts, $index + 1);
            $toPathParts = array_slice($toPathParts, $index + 1);

            return $fromPathParts === $toPathParts;
        }

        return false;
    }

    private function extractFolderRename(string $fromPath, string $toPath)
    {
        $fromPathParts = explode(DIRECTORY_SEPARATOR, $fromPath);
        $toPathParts = explode(DIRECTORY_SEPARATOR, $toPath);

        foreach ($fromPathParts as $index => $fromPathPart) {
            if ($fromPathPart === $toPathParts[$index]) {
                continue;
            }

            $folderPath = implode(DIRECTORY_SEPARATOR, array_slice($fromPathParts, 0, $index + 1));
            $name = $toPathParts[$index];

            return [$folderPath, $name];
        }

        throw new \Exception('Could not determine folder rename');
    }

    private function logIfVerbose(string $string)
    {
        if ($this->io->isVerbose()) {
            $this->io->text("<info>$string</info>");
        }
    }
}
