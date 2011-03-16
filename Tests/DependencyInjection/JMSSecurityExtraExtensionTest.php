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
        $this->assertTrue($container->hasDefinition('security.extra.controller_listener'));
        $this->assertFalse($container->getParameter('security.extra.secure_all_services'));
        $this->assertFalse($container->getDefinition('security.extra.iddqd_voter')->hasTag('security.voter'));
    }

    public function testConfigLoadSecureAll()
    {
        $extension = new JMSSecurityExtraExtension();
        $extension->load(array(array('secure_all_services' => true, 'secure_controllers' => false)), $container = new ContainerBuilder());

        $this->assertFalse($container->hasDefinition('security.extra.controller_listener'));
        $this->assertTrue($container->getParameter('security.extra.secure_all_services'));
    }

    public function testConfigLoadEnableIddqdAttribute()
    {
        $extension = new JMSSecurityExtraExtension();
        $extension->load(array(array('enable_iddqd_attribute' => true)), $container = new ContainerBuilder());

        $this->assertTrue($container->getDefinition('security.extra.iddqd_voter')->hasTag('security.voter'));
    }
}