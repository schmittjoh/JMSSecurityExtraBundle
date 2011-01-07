<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;

use Bundle\JMS\SecurityExtraBundle\DependencyInjection\Compiler\SecureMethodInvocationsPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class SecureMethodInvocationsTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessThrowsNoExceptionForUndefinedClassIfSecureAll()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('security.access.method_interceptor', new Definition());
        $container->setParameter('security.secured_services', array());

        $container->setDefinition('nonexistent.class', new Definition('FooBar'));

        $this->process($container);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testProcessThrowsExceptionForUndefinedClassIfNotSecureAll()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('security.access.method_interceptor', new Definition());
        $container->setParameter('security.secured_services', array('nonexistent.class'));

        $container->setDefinition('nonexistent.class', new Definition('FooBar'));

        $this->process($container);
    }

    protected function process(ContainerBuilder $container)
    {
        $pass = new SecureMethodInvocationsPass(sys_get_temp_dir());
        $pass->process($container);
    }
}