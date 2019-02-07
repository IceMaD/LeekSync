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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class FightSoloScriptsCommand extends LoggedCommand
{
    protected static $defaultName = 'fight:solo';

    const STRATEGY_MANUAL = 'manual';
    const STRATEGY_TALENT = 'talent';
    const STRATEGY_LEVEL = 'level';

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
        $this->setDescription('Launches your solo fights')
            ->addOption(
                'fights',
                'f',
                InputOption::VALUE_REQUIRED,
                'Number of fights to launch (default: 10)',
                5
            )
            ->addOption(
                'strategy',
                's',
                InputOption::VALUE_REQUIRED,
                'Strategy to choose the opponent manual|talent|level (default: manual)',
                self::STRATEGY_MANUAL
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Farmer $farmer */
        $farmer = $this->farmerApi->getFromToken()->wait()->getFarmer();

        $farmerFights = $farmer->getFights();

        $this->io->title("Welcome to the solo fight command, you have $farmerFights fights to do today");

        $leek = $this->askWhichLeek($farmer);

        for ($fights = 0; $fights < min($farmerFights, (int) $input->getOption('fights')); ++$fights) {
            $this->io->section('Fight #'.($fights + 1));

            /** @var GetLeekOpponentsResponse $garden */
            $garden = $this->gardenApi->getLeekOpponents($leek->getId())->wait();

            $sessionId = $garden->getPhpSessId();
            $opponents = $garden->getOpponents();

            $opponent = $this->askWhichOpponent($opponents, $this->getStrategy($input));

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

    private function askWhichOpponent(array $opponents, string $strategy): Leek
    {
        if (self::STRATEGY_MANUAL === $strategy) {
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

        return array_reduce($opponents, function (?Leek $opponent, Leek $nextLeek) use ($strategy) {
            if (!$opponent) {
                return $nextLeek;
            }

            if (self::STRATEGY_LEVEL === $strategy) {
                return $nextLeek->getLevel() < $opponent->getLevel() ? $nextLeek : $opponent;
            }

            if (self::STRATEGY_TALENT === $strategy) {
                return $nextLeek->getTalent() < $opponent->getTalent() ? $nextLeek : $opponent;
            }

            throw new \RuntimeException("Non managed strategy $strategy");
        });
    }

    private function getStrategy(InputInterface $input)
    {
        switch ($input->getOption('strategy')) {
            case self::STRATEGY_LEVEL:
                return self::STRATEGY_LEVEL;
            case self::STRATEGY_TALENT:
                return self::STRATEGY_TALENT;
            case self::STRATEGY_MANUAL:
            default:
                return self::STRATEGY_MANUAL;
        }
    }
}
