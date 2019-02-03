<?php

namespace App\Command;

use App\Api\AiApi;
use App\Api\InvalidTokenException;
use App\Api\RequestFailedException;
use App\Api\TokenStorage;
use App\Api\UserApi;
use App\TreeManagement\Builder;
use App\Watch\FileRegistry;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var UserApi
     */
    protected $userApi;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var FileRegistry
     */
    protected $registry;

    /**
     * @var AiApi
     */
    protected $aiApi;

    public function __construct(UserApi $userApi, AiApi $aiApi, FileRegistry $registry, TokenStorage $tokenStorage)
    {
        parent::__construct();

        $this->userApi = $userApi;
        $this->tokenStorage = $tokenStorage;
        $this->aiApi = $aiApi;
        $this->registry = $registry;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $outputStyle = new OutputFormatterStyle('red', null, ['bold']);
        $output->getFormatter()->setStyle('error', $outputStyle);

        $envLogin = getenv('APP_LOGIN') ? getenv('APP_LOGIN') : null;
        $envPassword = getenv('APP_PASSWORD') ? getenv('APP_PASSWORD') : null;

        $token = false;
        $login = null;

        if ($envLogin && $envPassword) {
            try {
                $login = $envLogin;
                $password = $envPassword;

                $token = $this->userApi->login($login, $password)->wait()->getToken();
            } catch (RequestFailedException $exception) {
                $this->io->error('The credentials provided in the .env are invalid');

                die;
            }
        } else {
            do {
                try {
                    $login = $this->io->ask('Login', $login ?? $envLogin);
                    $password = $this->io->askHidden('Password');

                    $token = $this->userApi->login($login, $password)->wait()->getToken();
                } catch (\Exception $exception) {
                    $this->io->error('Invalid credentials');
                }
            } while (!$token);
        }

        $this->tokenStorage->setToken($token);

        $root = Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait());

        $this->registry->init($this->aiApi->getTree($root));
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            return parent::run($input, $output);
        } catch (InvalidTokenException $exception) {
            $this->io->error('Your connexion expired, please reconnect');

            die;
        }
    }

    protected function logIfVerbose(string $string)
    {
        if ($this->io->isVerbose()) {
            $this->io->text("<info>$string</info>");
        }
    }
}
