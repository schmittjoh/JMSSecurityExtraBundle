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
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertFalse($container->getParameter('security.access.secure_all_services'));
        $this->assertFalse($container->getDefinition('security.extra.iddqd_voter')->hasTag('security.voter'));
    }

    public function testConfigLoadSecureAll()
    {
        $extension = new JMSSecurityExtraExtension();
        $extension->load(array(array('secure_all_services' => true)), $container = $this->getContainer());

        $this->assertTrue($container->getParameter('security.access.secure_all_services'));
    }

    public function testConfigLoadEnableIddqdAttribute()
    {
        $extension = new JMSSecurityExtraExtension();
        $extension->load(array(array('enable_iddqd_attribute' => true)), $container = $this->getContainer());

        $this->assertTrue($container->getDefinition('security.extra.iddqd_voter')->hasTag('security.voter'));
    }

    private function getContainer()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.bundles', array('JMSAopBundle' => 'JMS\AopBundle\JMSAopBundle'));

        return $container;
    }
}