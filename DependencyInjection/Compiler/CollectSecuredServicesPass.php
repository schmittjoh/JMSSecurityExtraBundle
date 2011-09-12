<?php

namespace JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Collects secured services.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class CollectSecuredServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $securedClasses = array();
        foreach ($container->findTaggedServiceIds('security.secure_service') as $id => $attr) {
            $securedClasses[] = $container->getDefinition($id)->getClass();
        }

        $container
            ->getDefinition('security.access.pointcut')
            ->addMethodCall('setSecuredClasses', array($securedClasses))
        ;
    }
}