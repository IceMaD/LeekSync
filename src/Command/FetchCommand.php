<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\Model\Ai;
use App\TreeManagement\Builder;
use App\TreeManagement\ConflictException;
use App\TreeManagement\Dumper;
use App\Watch\FileRegistry;
use DusanKasan\Knapsack\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FetchCommand extends Command
{
    protected static $defaultName = 'scripts:fetch';

    /**
     * @var Dumper
     */
    private $dumper;

    public function __construct(UserApi $userApi, AiApi $aiApi, FileRegistry $registry, TokenStorage $tokenStorage, Dumper $dumper)
    {
        parent::__construct($userApi, $aiApi, $registry, $tokenStorage);

        $this->dumper = $dumper;
    }

    protected function configure()
    {
        $this->setDescription('Fetches scripts from your account')
            ->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var Ai[][]|ConflictException[][]
         */
        $ais = $this->dumper->dump($this->registry->getTree(), $input->getOption('force'));

        foreach ($ais['fetched'] as $ai) {
            $this->io->text("<info>{$ai->getPath()}</info> fetched");
        }

        foreach ($ais['conflicts'] as $exception) {
            $this->io->text("<error>{$exception->getAi()->getPath()}</error> has conflict");
            $this->io->text(
                Collection::from(explode("\n", $exception->getDiffView()))
                    ->map(function (string $line) {
                        switch (substr($line, 0, 1)) {
                            case '-':
                                return "<error>$line</error>";
                            case '+':
                                return "<info>$line</info>";
                            default:
                                return $line;
                        }
                    })
                    ->toArray()
            );
        }

        if (!empty($ais['conflicts'])) {
            $this->io->error([
                'Some scripts had conflicts.',
                'Fix them manually or use --force to override local data',
            ]);

            return;
        }

        $this->io->success('You have a successfully fetched all your scripts');
    }
}
