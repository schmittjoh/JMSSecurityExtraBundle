<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Bundle\JMS\SecurityExtraBundle\DependencyInjection\SecurityExtraExtension;

class SecurityExtraExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigLoadWithEmptyConfig()
    {
        $extension = new SecurityExtraExtension();

        $config = array();
        $extension->configLoad($config, $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertEquals(array(), $container->getParameter('security.secured_services'));
    }

    /**
     * @dataProvider getEquivalentConfigData
     */
    public function testConfigLoad(array $config)
    {
        $extension = new SecurityExtraExtension();
        $extension->configLoad($config, $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertEquals(array(
            'foo' => array(),
            'bar' => array(),
        ), $container->getParameter('security.secured_services'));
    }

    public function getEquivalentConfigData()
    {
        return array(
            array(array('services' => array('foo', 'bar'))),
            array(array('services' => array('foo' => null, 'bar' => null))),
        );
    }
}