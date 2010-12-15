<?php

namespace Bundle\JMS\SecurityExtraBundle;

use Bundle\JMS\SecurityExtraBundle\DependencyInjection\Compiler\SecureMethodInvocationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SecurityExtraBundle extends Bundle
{
    public function registerExtensions(ContainerBuilder $container)
    {
        parent::registerExtensions($container);

        $passConfig = $container->getCompilerPassConfig();
        $passConfig->addPass(new SecureMethodInvocationsPass(
            $container->getParameter('kernel.cache_dir')
        ));
    }
}