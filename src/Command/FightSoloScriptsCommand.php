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
        $this->setDescription('Lancer des combats de poireau solo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Farmer $farmer */
        $farmer = $this->farmerApi->getFromToken()->wait()->getFarmer();

        $farmerFights = $farmer->getFights();

        $this->io->title("Bienvenue dans la commande pour lancer des combat de poireau solo, vous avez $farmerFights combats restant");

        $strategy = $this->askWhichStrategy();
        $fightsCount = $this->askFightCount();
        $leek = $this->askWhichLeek($farmer);

        for ($fights = 0; $fights < min($farmerFights, $fightsCount); ++$fights) {
            $this->io->section('Fight #'.($fights + 1));

            /** @var GetLeekOpponentsResponse $garden */
            $garden = $this->gardenApi->getLeekOpponents($leek->getId())->wait();

            $sessionId = $garden->getPhpSessId();
            $opponents = $garden->getOpponents();

            $opponent = $this->askWhichOpponent($opponents, $strategy);

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
                $this->io->error('Le combat n\'est toujours pas terminé. Il semblerait que le serveur lag, Essayez de relancer vos combats plus tard');
                die;
            }

            switch ($fight->getWinner()) {
                case 0:
                    $this->io->block("Egalité avec {$opponent->getName()}", "\u{2012}", 'fg=black;bg=cyan', ' ', true);
                    break;
                case 1:
                    $this->io->block("Vous avez gagné contre {$opponent->getName()}", "\u{2713}", 'fg=black;bg=green', ' ', true);
                    break;
                case 2:
                    $this->io->block("Vous avez perdu contre {$opponent->getName()}", "\u{2715}", 'fg=black;bg=red', ' ', true);
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

        $leekChoice = new ChoiceQuestion('Quel poireau voulez vous utiliser ?', array_map(function (Leek $leek) {
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

            $leekChoice = new ChoiceQuestion('Quel poireau voulez vous affronter ?', array_map(function (Leek $leek) {
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

    private function askWhichStrategy(): string
    {
        return $this->io->askQuestion(new ChoiceQuestion('Quelle stratégie de choix d\'adversaire voulez vous utiliser ?', [
            self::STRATEGY_MANUAL => 'Manuel - Choisir l\'adversaire manuellement',
            self::STRATEGY_TALENT => 'Talent - Choisir automatiquement le poireau qui a le moins de talent',
            self::STRATEGY_LEVEL => 'Niveau - Choisir automatiquement le poireau qui a le plus petit niveau',
        ]));
    }

    private function askFightCount(): int
    {
        return (int) $this->io->ask('Combien de combats voulez vous faire ?', 1, function (string $input) {
            if (!preg_match('/^\d+$/', $input)) {
                throw new \RuntimeException('Veuillez saisir un numéro');
            }

            return $input;
        });
    }
}
