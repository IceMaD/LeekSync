<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PushCommand extends Command
{
    protected static $defaultName = 'scripts:push';

    protected function configure()
    {
        $this->setDescription('Push scripts to your account');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->registry->getAis() as $path => $ai) {
            $this->aiApi->updateAiCode($ai, $ai->getCode())->wait();

            if ($ai->isValid()) {
                $this->io->text("<info>{$ai->getPath()}</info> Successfuly synced !");
            } else {
                $error = $ai->getError();

                $errorAi = $this->registry->findAiById($error->getErroredAiId());

                $this->io->text("<info>{$ai->getPath()}</info> Synced but is invalid du to error in <error>{$errorAi->getPath()}</error> line {$error->getLine()} \u{02023} ({$error->getCharacter()}) {$error->getError()}");
            }
        }

        $this->io->success('You have a successfully pushed all your scripts');
    }
}
