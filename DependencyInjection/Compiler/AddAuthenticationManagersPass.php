<?php

namespace Bundle\JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddAuthenticationManagersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('security.authentication.extra_manager')) {
            return;
        }

        $managers = array();
        foreach ($container->getDefinitions() as $id => $definition) {
            if ((null === $class = $definition->getClass()) || !class_exists($class)) {
                continue;
            }

            if ('security.authentication.extra_manager' === $id) {
                continue;
            }

            $class = new \ReflectionClass($class);
            if ($class->isSubClassOf('Symfony\Component\Security\Authentication\AuthenticationManagerInterface')) {
                $managers[] = new Reference($id);
            }
        }

        $container
            ->getDefinition('security.authentication.extra_manager')
            ->setArguments(array($managers))
        ;
    }
}