<?php

namespace JMS\SecurityExtraBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use JMS\SecurityExtraBundle\DependencyInjection\JMSSecurityExtraExtension;

class JMSSecurityExtraExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigLoadWithEmptyConfig()
    {
        $extension = new JMSSecurityExtraExtension();

        $config = array();
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertEquals(array(), $container->getParameter('security.secured_services'));
    }

    /**
     * @dataProvider getEquivalentConfigData
     */
    public function testConfigLoad(array $config)
    {
        $extension = new JMSSecurityExtraExtension();
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertEquals(array(
            'foo' => array(),
            'bar' => array(),
        ), $container->getParameter('security.secured_services'));
    }

    public function getEquivalentConfigData()
    {
        return array(
            array(array('service' => array(array('id' => 'foo'), array('id' => 'bar')))),
            array(array('services' => array('foo', 'bar'))),
            array(array('services' => array('foo' => null, 'bar' => null))),
        );
    }
}