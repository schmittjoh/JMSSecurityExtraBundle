<?php

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use JMS\SecurityExtraBundle\Mapping\Driver\AnnotationDriver;

require_once __DIR__.'/Fixtures/services.php';

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataFromClass()
    {
        $driver = new AnnotationDriver();

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));
        $this->assertTrue($metadata->hasMethod('foo'));
        $method = $metadata->getMethod('foo');
        $this->assertEquals(array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'), $method->getRoles());
        $this->assertEquals(array(), $method->getReturnPermissions());
        $this->assertEquals(array(0 => array('VIEW')), $method->getParamPermissions());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooInterface'));
        $this->assertTrue($metadata->hasMethod('foo'));
        $method = $metadata->getMethod('foo');
        $this->assertEquals(array(), $method->getRoles());
        $this->assertEquals(array(0 => array('OWNER'), 1 => array('EDIT')), $method->getParamPermissions());
        $this->assertEquals(array('MASTER'), $method->getReturnPermissions());
    }
}