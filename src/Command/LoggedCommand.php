<?php

namespace App\Command;

use IceMaD\LeekWarsApiBundle\Api\FarmerApi;
use IceMaD\LeekWarsApiBundle\Exception\InvalidTokenException;
use IceMaD\LeekWarsApiBundle\Exception\RequestFailedException;
use IceMaD\LeekWarsApiBundle\Storage\TokenStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class LoggedCommand extends Command
{
    /**
     * @var FarmerApi
     */
    protected $farmerApi;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    public function __construct(FarmerApi $farmerApi, TokenStorage $tokenStorage)
    {
        parent::__construct();

        $this->farmerApi = $farmerApi;
        $this->tokenStorage = $tokenStorage;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

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

                $token = $this->farmerApi->loginToken($login, $password)->wait()->getToken();
            } catch (RequestFailedException $exception) {
                $this->io->error('The credentials provided in the .env are invalid');

                die;
            }
        } else {
            do {
                try {
                    $login = $this->io->ask('Login', $login ?? $envLogin);
                    $password = $this->io->askHidden('Password');

                    $token = $this->farmerApi->loginToken($login, $password)->wait()->getToken();
                } catch (\Exception $exception) {
                    $this->io->error('Invalid credentials');
                }
            } while (!$token);
        }

        $this->tokenStorage->setToken($token);
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
}
