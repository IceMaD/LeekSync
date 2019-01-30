<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\Model\Ai;
use App\TreeManagement\Builder;
use App\TreeManagement\ConflictException;
use App\TreeManagement\Dumper;
use DusanKasan\Knapsack\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FetchCommand extends Command
{
    protected static $defaultName = 'app:fetch';

    /**
     * @var AiApi
     */
    private $aiApi;

    /**
     * @var Dumper
     */
    private $dumper;

    public function __construct(UserApi $userApi, TokenStorage $tokenStorage, AiApi $aiApi, Dumper $dumper)
    {
        parent::__construct($userApi, $tokenStorage);

        $this->aiApi = $aiApi;
        $this->dumper = $dumper;
    }

    protected function configure()
    {
        $this->setDescription('Fetches scripts from your account')
            ->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $tree = Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait());

        /**
         * @var $ais Ai[][]|ConflictException[][]
         */
        $ais = $this->dumper->dump($this->aiApi->getTree($tree), $input->getOption('force'));

        foreach ($ais['fetched'] as $ai) {
            $io->text("<info>{$ai->getPath()}</info> fetched");
        }

        foreach ($ais['conflicts'] as $exception) {
            $io->text("<error>{$exception->getAi()->getPath()}</error> has conflict");
            $io->text(
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
            $io->error([
                'Some scripts had conflicts.',
                'Fix them manually or use --force to override local data'
            ]);

            return;
        }

        $io->success('You have a successfully fetched all your scripts');
    }

}
