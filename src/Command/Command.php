<?php

namespace App\Command;

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
        parent::initialize($input, $output);

        $io = new SymfonyStyle($input, $output);

        $outputStyle = new OutputFormatterStyle('red', null, ['bold']);
        $output->getFormatter()->setStyle('error', $outputStyle);

        $login = getenv('APP_LOGIN') ?? $io->ask('Login');
        $password = getenv('APP_PASSWORD') ?? $io->askHidden('Password');
        $token = $this->userApi->login($login, $password)->wait()->token;

        $this->tokenStorage->setToken($token);
    }
}
