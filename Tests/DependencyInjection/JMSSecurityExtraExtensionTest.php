<?php

namespace JMS\SecurityExtraBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use JMS\SecurityExtraBundle\DependencyInjection\JMSSecurityExtraExtension;

class JMSSecurityExtraExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigLoad()
    {
        $extension = new JMSSecurityExtraExtension();

        $config = array();
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertFalse($container->getParameter('security.extra.secure_all'));
    }

    public function testConfigLoadSecureAll()
    {
        $extension = new JMSSecurityExtraExtension();
        $extension->load(array(array('secure_all' => true)), $container = new ContainerBuilder());

        $this->assertTrue($container->getParameter('security.extra.secure_all'));
    }
}