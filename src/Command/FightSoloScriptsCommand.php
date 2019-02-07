<?php

namespace App\Command;

use IceMaD\LeekWarsApiBundle\Api\FarmerApi;
use IceMaD\LeekWarsApiBundle\Api\FightApi;
use IceMaD\LeekWarsApiBundle\Api\GardenApi;
use IceMaD\LeekWarsApiBundle\Entity\Farmer;
use IceMaD\LeekWarsApiBundle\Entity\Fight;
use IceMaD\LeekWarsApiBundle\Entity\Leek;
use IceMaD\LeekWarsApiBundle\Response\Garden\GetLeekOpponentsResponse;
use IceMaD\LeekWarsApiBundle\Response\Garden\StartSoloFightResponse;
use IceMaD\LeekWarsApiBundle\Storage\TokenStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class FightSoloScriptsCommand extends LoggedCommand
{
    protected static $defaultName = 'fight:solo';

    /**
     * @var FightApi
     */
    private $fightApi;

    /**
     * @var GardenApi
     */
    private $gardenApi;

    public function __construct(FarmerApi $farmerApi, GardenApi $gardenApi, FightApi $fightApi, TokenStorage $tokenStorage)
    {
        parent::__construct($farmerApi, $tokenStorage);

        $this->fightApi = $fightApi;
        $this->farmerApi = $farmerApi;
        $this->gardenApi = $gardenApi;
    }

    protected function configure()
    {
        $this->setDescription('Launches your solo fights');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Farmer $farmer */
        $farmer = $this->farmerApi->getFromToken()->wait()->getFarmer();

        $leek = $this->askWhichLeek($farmer);

        for ($fights = $farmer->getFights(); $fights > 0; --$fights) {
            /** @var GetLeekOpponentsResponse $garden */
            $garden = $this->gardenApi->getLeekOpponents($leek->getId())->wait();

            $sessionId = $garden->getPhpSessId();
            $opponents = $garden->getOpponents();

            $opponent = $this->askWhichOpponent($opponents);

            /** @var StartSoloFightResponse $gardenFightResponse */
            $gardenFightResponse = $this->gardenApi->startSoloFight($leek->getId(), $opponent->getId(), $sessionId)->wait();

            sleep(1);

            $this->io->progressStart(10);

            for ($progress = 0; $progress < 10; ++$progress) {
                /** @var Fight $fight */
                $fight = $this->fightApi->getFight($gardenFightResponse->getFight())->wait()->getFight();

                if ($fight->isEnded()) {
                    break;
                }

                $this->io->progressAdvance();
                sleep(5);
            }

            $this->io->progressFinish();

            if (!$fight->isEnded()) {
                $this->io->error('Fight did not end. The server seems to lag, please try again later');
                die;
            }

            switch ($fight->getWinner()) {
                case 0:
                    $this->io->block("Draw with {$opponent->getName()}", 'OK', 'fg=black;bg=cyan', ' ', true);
                    break;
                case 1:
                    $this->io->success("You won against {$opponent->getName()}");
                    break;
                case 2:
                    $this->io->error("You lost against {$opponent->getName()}");
                    break;
            }
        }
    }

    private function askWhichLeek(Farmer $farmer): Leek
    {
        $leeks = [];

        foreach ($farmer->getLeeks() as $leek) {
            $leeks[$leek->getName()] = $leek;
        }

        $leekChoice = new ChoiceQuestion('Which leek do you want to use ?', array_map(function (Leek $leek) {
            return "{$leek->getName()} ({$leek->getLevel()})";
        }, $leeks));

        return $leeks[$this->io->askQuestion($leekChoice)];
    }

    private function askWhichOpponent(array $opponents): Leek
    {
        $leeks = [];

        foreach ($opponents as $leek) {
            $leeks[$leek->getName()] = $leek;
        }

        $this->io->table(['Leek', 'Level', 'Talent'], array_map(function (Leek $leek) {
            return [$leek->getName(), $leek->getLevel(), $leek->getTalent()];
        }, $leeks));

        $leekChoice = new ChoiceQuestion('Which leek do you want to use ?', array_map(function (Leek $leek) {
            return "{$leek->getName()}";
        }, $leeks));

        return $leeks[$this->io->askQuestion($leekChoice)];
    }
}
