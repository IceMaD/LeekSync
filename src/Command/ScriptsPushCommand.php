<?php

namespace App\Command;

use function GuzzleHttp\Promise\unwrap;
use IceMaD\LeekWarsApiBundle\Response\Ai\SaveResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScriptsPushCommand extends ScriptsCommand
{
    protected static $defaultName = 'scripts:push';

    protected function configure()
    {
        $this->setDescription('Envoie les fichiers sur le site LeekWars');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $promises = [];

        foreach ($this->fileRegistry->getAis() as $path => $ai) {
            $promises[] = $this->aiApi->save($ai->getId(), $ai->getCode())
                ->then(function (SaveResponse $response) use ($ai) {
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

                    return $ai;
                });
        }

        unwrap($promises);

        $this->io->success('Les fichiers ont bien été envoyé sur le site LeekWars');
    }
}
