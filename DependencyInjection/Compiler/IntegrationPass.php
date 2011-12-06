<?php

namespace JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class IntegrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('security.acl.provider')
            && !$container->hasDefinition('security.acl.provider')) {
            $container->removeDefinition('security.acl.permission_evaluator');
        }

        if ($container->hasDefinition('security.role_hierarchy')) {
            $container->getDefinition('security.role_hierarchy')
                ->setPublic(true);
        }
    }
}