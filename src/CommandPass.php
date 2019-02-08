<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Command\AboutCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommandPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $env = $container->getParameter('kernel.environment');

        if ('prod' !== $env) {
            return;
        }

        foreach ($container->findTaggedServiceIds('console.command') as $commandID => $dunno) {
            $definition = $container->getDefinition($commandID);

            $commandClass = $definition->getClass();

            if (!preg_match('/^App/', $commandClass) && HelpCommand::class !== $commandClass) {
                $definition->clearTag('console.command');
            }
        }
    }
}
