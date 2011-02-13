<?php

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use JMS\SecurityExtraBundle\Mapping\Driver\DriverChain;

require_once __DIR__.'/Fixtures/services.php';

class DriverChainTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataFromClass()
    {
        $driver = new DriverChain();
        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));

        $this->assertEquals(true, $metadata->hasMethod('foo'));
        $method = $metadata->getMethod('foo');
        $this->assertEquals(array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'), $method->getRoles());
        $this->assertEquals(array(), $method->getReturnPermissions());
        $this->assertEquals(array(array('VIEW')), $method->getParamPermissions());
    }
}