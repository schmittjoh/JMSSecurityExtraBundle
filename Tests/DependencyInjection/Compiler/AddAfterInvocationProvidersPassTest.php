<?php

namespace JMS\SecurityExtraBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use JMS\SecurityExtraBundle\DependencyInjection\Compiler\AddAfterInvocationProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddAfterInvocationProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessStopsWhenNoAfterInvocationManager()
    {
        $container = $this->getContainer();
        $container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('security.access.after_invocation_manager'))
            ->will($this->returnValue(false))
        ;
        $container
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $this->process($container);
    }

    public function testProcessRemovesAclProviderIfAclIsNotActive()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('security.access.after_invocation_manager', $manager = new Definition());

        $container
            ->register('security.access.after_invocation.acl_provider')
            ->addTag('security.after_invocation.provider')
        ;

        $this->assertEquals(array(), $manager->getArguments());
        $this->process($container);
        $this->assertEquals(array(array()), $manager->getArguments());
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->setDefinition('security.access.after_invocation_manager', $manager = new Definition());

        $provider1 = new Definition();
        $provider1->addTag('security.after_invocation.provider');
        $container->setDefinition('provider1', $provider1);

        $provider2 = new Definition();
        $provider2->addTag('security.after_invocation.provider');
        $container->setDefinition('provider2', $provider2);

        $this->process($container);

        $arguments = $manager->getArguments();
        $this->assertEquals(2, count($providers = $arguments[0]));
        $this->assertEquals('provider1', (string) $providers[0]);
        $this->assertEquals('provider2', (string) $providers[1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $pass = new AddAfterInvocationProvidersPass();
        $pass->process($container);
    }

    protected function getContainer()
    {
        return $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
    }
}