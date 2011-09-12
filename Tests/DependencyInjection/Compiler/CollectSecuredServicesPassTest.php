<?php

namespace JMS\SecurityExtraBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use JMS\SecurityExtraBundle\DependencyInjection\Compiler\CollectSecuredServicesPass;

class CollectSecuredServicesPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('security.access.pointcut', 'JMS\SecurityExtraBundle\Security\Authorization\Interception\SecurityPointcut')
        ;

        $container
            ->register('a', 'stdClass')
            ->addTag('security.secure_service')
        ;
        $container
            ->register('b', 'stdClass')
            ->addTag('security.secure_service')
        ;

        $pass = new CollectSecuredServicesPass();
        $pass->process($container);

        $this->assertEquals(array(
            array('setSecuredClasses', array(array('stdClass', 'stdClass'))),
        ), $container->getDefinition('security.access.pointcut')->getMethodCalls());
    }
}