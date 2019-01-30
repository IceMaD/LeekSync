<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\TreeManagement\Builder;
use App\TreeManagement\ConflictException;
use App\TreeManagement\Dumper;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->setDescription('Fetches scripts from your account');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $tree = Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait());

        try {
            $this->dumper->dump($this->aiApi->getTree($tree));
        } catch (ConflictException $exception) {
            $io->error("Conflict on {$exception->getPath()}");
            $io->text($exception->getDiffView());

            return;
        }

        $io->success('You have a successfully fetched all your scripts');
    }

}
