<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use Bundle\JMS\SecurityExtraBundle\Mapping\Driver\AnnotationDriver;

require_once __DIR__.'/Fixtures/services.php';

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataFromClass()
    {
        $driver = new AnnotationDriver();
        $metadata = $driver->loadMetadataForClass('Bundle\JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService');

        $this->assertTrue($metadata->hasMethod('foo'));
        $method = $metadata->getMethod('foo');
        $this->assertEquals(array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'), $method->getRoles());
        $this->assertEquals(array(), $method->getReturnPermissions());
        $this->assertEquals(array('param' => array('VIEW')), $method->getParamPermissions());
    }
}