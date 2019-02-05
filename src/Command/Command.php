<?php

namespace App\Command;

use App\TreeManagement\Builder;
use App\Watch\FileRegistry;
use DusanKasan\Knapsack\Collection;
use IceMaD\LeekWarsApiBundle\Api\AiApi;
use IceMaD\LeekWarsApiBundle\Api\FarmerApi;
use IceMaD\LeekWarsApiBundle\Entity\Ai;
use IceMaD\LeekWarsApiBundle\Entity\Folder;
use IceMaD\LeekWarsApiBundle\Exception\InvalidTokenException;
use IceMaD\LeekWarsApiBundle\Exception\RequestFailedException;
use IceMaD\LeekWarsApiBundle\Storage\TokenStorage;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends \Symfony\Component\Console\Command\Command
{

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var AiApi
     */
    protected $aiApi;
    /**
     * @var FarmerApi
     */
    protected $farmerApi;

    /**
     * @var FileRegistry
     */
    protected $fileRegistry;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    public function __construct(AiApi $aiApi, FarmerApi $farmerApi, FileRegistry $fileRegistry, TokenStorage $tokenStorage)
    {
        parent::__construct();

        $this->aiApi = $aiApi;
        $this->farmerApi = $farmerApi;
        $this->fileRegistry = $fileRegistry;
        $this->tokenStorage = $tokenStorage;
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

        $root = Builder::buildFolderTree($this->aiApi->getFarmerAIs()->wait());

        $this->fileRegistry->init($this->getTree($root));
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

    public function getTree(Folder $root)
    {
        $root->setAis(
            Collection::from($root->getAis())
                ->map(function (Ai $ai) {
                    $ai->setCode($this->aiApi->getAI($ai->getId())->wait()->getAi()->getCode());

                    return $ai;
                })
                ->toArray()
        );

        $root->setFolders(
            Collection::from($root->getFolders())
                ->map(function (Folder $folder) {
                    return $this->getTree($folder);
                })
                ->toArray()
        );

        return $root;
    }
}
