<?php

namespace App\Command;

use App\Api\InvalidTokenException;
use App\Api\RequestFailedException;
use App\Api\TokenStorage;
use App\Api\UserApi;
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

    public function __construct(UserApi $userApi, TokenStorage $tokenStorage)
    {
        parent::__construct();

        $this->userApi = $userApi;
        $this->tokenStorage = $tokenStorage;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

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
                $io->error('The credentials provided in the .env are invalid');

                die;
            }
        } else {
            do {
                try {
                    $login = $io->ask('Login', $login ?? $envLogin);
                    $password = $io->askHidden('Password');

                    $token = $this->userApi->login($login, $password)->wait()->getToken();
                } catch (\Exception $exception) {
                    $io->error('Invalid credentials');
                }
            } while (!$token);
        }

        $this->tokenStorage->setToken($token);
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            return parent::run($input, $output);
        } catch (InvalidTokenException $exception) {
            $io->error('Your connexion expired, please reconnect');

            die;
        }
    }
}
