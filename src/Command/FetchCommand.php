<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\TreeManagement\Builder;
use App\TreeManagement\ConflictException;
use App\TreeManagement\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FetchCommand extends Command
{
    protected static $defaultName = 'app:fetch';

    /**
     * @var UserApi
     */
    private $userAPI;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var AiApi
     */
    private $aiApi;

    /**
     * @var Dumper
     */
    private $dumper;

    public function __construct(UserApi $userAPI, TokenStorage $tokenStorage, AiApi $aiApi, Dumper $dumper)
    {
        parent::__construct(null);
        $this->userAPI = $userAPI;
        $this->tokenStorage = $tokenStorage;
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

        $login = getenv('APP_LOGIN') ?? $io->ask('Login');
        $password = getenv('APP_PASSWORD') ?? $io->askHidden('Password');
        $token = $this->userAPI->login($login, $password)->wait()->token;

        $this->tokenStorage->setToken($token);

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
