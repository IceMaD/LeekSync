<?php

namespace App\Command;

use App\Async\Deferred;
use App\Async\Pool;
use App\TreeManagement\Dumper;
use App\Watch\FileRegistry;
use App\Watch\Watcher;
use IceMaD\LeekWarsApiBundle\Api\AiApi;
use IceMaD\LeekWarsApiBundle\Api\AiFolderApi;
use IceMaD\LeekWarsApiBundle\Api\FarmerApi;
use IceMaD\LeekWarsApiBundle\Entity\Ai;
use IceMaD\LeekWarsApiBundle\Entity\Folder;
use IceMaD\LeekWarsApiBundle\Response\Ai\SaveResponse;
use IceMaD\LeekWarsApiBundle\Storage\TokenStorage;
use JasonLewis\ResourceWatcher\Resource\ResourceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScriptsWatchCommand extends ScriptsCommand
{
    protected static $defaultName = 'scripts:watch';

    /**
     * @var Watcher
     */
    private $watcher;

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
     * @var AiFolderApi
     */
    private $folderApi;

    public function __construct(
        AiApi $aiApi,
        AiFolderApi $folderApi,
        Dumper $dumper,
        FarmerApi $farmerApi,
        FileRegistry $registry,
        TokenStorage $tokenStorage,
        Watcher $watcher,
        string $scriptsDir
    ) {
        parent::__construct($aiApi, $farmerApi, $registry, $tokenStorage);

        $this->dumper = $dumper;
        $this->folderApi = $folderApi;
        $this->watcher = $watcher;

        // @TODO Fix Pool design problem. Lib can not have multiple pool
        $this->deletionsPool = Pool::create();
        $this->updatesPool = Pool::create();

        $this->extension = getenv('APP_FILE_EXTENSION');
        $this->scriptsDir = $scriptsDir;
    }

    protected function configure()
    {
        $this->setDescription('Watches for files changes to push them on LeekWars.com');
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
            $this->updateCode($path);
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

    /**
     * @throws \Exception
     */
    private function createAi(string $path)
    {
        [$folderPath, $name] = $this->guessPathParts($path);

        if (!$this->fileRegistry->hasFolder($folderPath)) {
            $this->createFolder($folderPath);
        }

        $ai = (new Ai())->setName($name)
            ->setFolder($this->fileRegistry->fetchFolder($folderPath))
            ->setCode(file_get_contents($path));

        $ai->setId($this->aiApi->new($ai->getFolder()->getId())->wait()->getAi()->getId());

        $this->aiApi->rename($ai->getId(), $ai->getName())->wait();
        $this->aiApi->save($ai->getId(), $ai->getCode())->then(function (SaveResponse $response) use ($ai) {
            if ($response->isAiValid()) {
                $ai->setValid(true);

                $this->io->text("<info>{$ai->getPath()}</info> Successfully created !");
            } else {
                $ai->setValid(false);
                $ai->setError($response->getAiError());

                $error = $ai->getError();

                $errorAi = $this->fileRegistry->findAiById($error->getErroredAiId());

                $this->io->text("<info>{$ai->getPath()}</info> Created but is invalid du to error in <error>{$errorAi->getPath()}</error> line {$error->getLine()} \u{02023} ({$error->getCharacter()}) {$error->getError()}");
            }

            $this->fileRegistry->pushAi($ai);
        })->wait();
    }

    /**
     * @throws \Exception
     */
    private function updateCode(string $path)
    {
        $ai = $this->fileRegistry->fetchAi($path);

        $code = file_get_contents($path);

        $this->aiApi->save($ai->getId(), $code)->then(function (SaveResponse $response) use ($path, $ai) {
            if ($response->isAiValid()) {
                $ai->setValid(true);

                $this->io->text("<info>{$ai->getPath()}</info> Successfuly synced !");
            } else {
                $ai->setValid(false);
                $ai->setError($response->getAiError());

                $error = $ai->getError();

                $errorAi = $this->fileRegistry->findAiById($error->getErroredAiId());

                $this->io->text("<info>{$ai->getPath()}</info> Synced but is invalid du to error in <error>{$errorAi->getPath()}</error> line {$error->getLine()} \u{02023} ({$error->getCharacter()}) {$error->getError()}");
            }

            $this->fileRegistry->moveAi($ai, $path);
        })->wait();
    }

    /**
     * @throws \Exception
     */
    private function createFolder(string $path)
    {
        [$parentFolderPath, $name] = $this->guessPathParts($path);

        if (!$this->fileRegistry->hasFolder($parentFolderPath)) {
            $this->createFolder($parentFolderPath);
        }

        $folder = (new Folder())
            ->setName($name)
            ->setFolder($this->fileRegistry->fetchFolder($parentFolderPath));

        $folder->setId($this->folderApi->new($folder->getFolder()->getId())->wait()->getId());
        $this->folderApi->rename($folder->getId(), $folder->getName())->wait();
        $this->fileRegistry->pushFolder($folder);

        $this->io->text("<info>{$folder->getPath()}</info> Successfully created !");
    }

    /**
     * @throws \Exception
     */
    private function renameAi(string $fromPath, string $path)
    {
        [, $name] = $this->guessPathParts($path);

        $ai = $this->fileRegistry->fetchAi($fromPath);
        $ai->setName($name);

        $this->aiApi->rename($ai->getId(), $ai->getName())->wait();
        $this->fileRegistry->moveAi($ai, $fromPath);

        $this->io->text("<info>{$ai->getPath()}</info> Successfully renamed !");
    }

    /**
     * @throws \Exception
     */
    private function renameFolder(string $fromPath, string $path)
    {
        [$folderPath, $name] = $this->extractFolderRename($fromPath, $path);

        $folder = $this->fileRegistry->fetchFolder($folderPath);
        $folder->setName($name);

        $this->folderApi->rename($folder->getId(), $folder->getName())->wait();
        $this->fileRegistry->moveFolder($folder, $fromPath);

        $this->io->text("<info>{$folder->getPath()}</info> Successfully renamed !");
    }

    /**
     * @throws \Exception
     */
    private function moveAi(string $fromPath, string $path)
    {
        [$folderPath] = $this->guessPathParts($path);

        if (!$this->fileRegistry->hasFolder($folderPath)) {
            $this->createFolder($folderPath);
        }

        $ai = $this->fileRegistry->fetchAi($fromPath);
        $folder = $this->fileRegistry->fetchFolder($folderPath);
        $ai->getFolder()->removeAi($ai);
        $folder->addAi($ai);

        $this->aiApi->changeFolder($ai->getId(), $folder->getId())->wait();
        $this->fileRegistry->moveAi($ai, $fromPath);

        $this->io->text("<info>{$ai->getPath()}</info> Successfully moved !");
    }

    /**
     * @throws \Exception
     */
    private function moveFolder(string $folderPath, string $destinationParentFolderPath)
    {
        $folder = $this->fileRegistry->fetchFolder($folderPath);
        $destinationParentFolder = $this->fileRegistry->fetchFolder($destinationParentFolderPath);
        $folder->getFolder()->removeFolder($folder);
        $destinationParentFolder->addFolder($folder);

        $this->folderApi
            ->changeFolder($folder->getId(), $destinationParentFolder->getId())
            ->wait();

        $this->fileRegistry->moveFolder($folder, $folderPath);

        $this->io->text("<info>{$folder->getPath()}</info> Successfully moved !");
    }

    /**
     * @throws \Exception
     */
    private function scheduleAiDeletion(string $path)
    {
        $this->scheduledDeletions[] = $path;
        $this->deletionsPool = Pool::create();

        $deletion = new Deferred(function () {
            foreach ($this->scheduledDeletions as $path) {
                [$folderPath] = $this->guessPathParts($path);

                if (!file_exists($folderPath) && $this->fileRegistry->hasFolder($folderPath)) {
                    $folder = $this->fileRegistry->fetchFolder($folderPath);

                    $this->folderApi->delete($folder->getId())->wait();
                    $this->fileRegistry->deleteFolder($folder);

                    $this->io->text("<error>{$folder->getPath()}</error> Successfully deleted !");
                }

                $ai = $this->fileRegistry->fetchAi($path);

                $this->aiApi->delete($ai->getId())->wait();
                $this->fileRegistry->deleteAi($ai);

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
                $this->logIfVerbose("Rename AI - $fromPath - $toPath");
                $this->renameAi($fromPath, $toPath);
            } elseif ($this->isFolderRename($fromPath, $toPath)) {
                $this->logIfVerbose("Rename Folder - $fromPath - $toPath");
                $this->renameFolder($fromPath, $toPath);
            } elseif ($fromParentFolderPath === $toParentFolderPath) {
                $this->logIfVerbose("Move Folder - $fromPath - $toPath");
                [$currentFolderPath, $destinationFolderPath] = $this->extractFolderMovement($fromPath, $toPath);

                $this->moveFolder($currentFolderPath, $destinationFolderPath);
            } else {
                $this->logIfVerbose("Move Ai - $fromPath - $toPath");
                foreach ($this->scheduledDeletions as $key => $fromPath) {
                    $this->moveAi($fromPath, $this->scheduledUpdates[$key]);
                }
            }

            $this->scheduledDeletions = [];
            $this->scheduledUpdates = [];
        });

        $this->updatesPool->add($update->getProcess());
    }

    /**
     * @throws \Exception
     */
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

        if (count($fromPathParts) !== count($toPathParts)) {
            return false;
        }

        [$fromPath] = $this->guessPathParts($fromPath);
        [$toPath] = $this->guessPathParts($toPath);

        return !file_exists($fromPath) && is_dir($toPath);
    }

    /**
     * @throws \Exception
     */
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
}
