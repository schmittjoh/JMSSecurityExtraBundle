<?php

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use JMS\SecurityExtraBundle\Metadata\Driver\AnnotationDriver;

require_once __DIR__.'/Fixtures/services.php';

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataFromClass()
    {
        $driver = new AnnotationDriver();

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));
        $this->assertTrue(isset($metadata->methodMetadata['foo']));
        $method = $metadata->methodMetadata['foo'];
        $this->assertEquals(array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'), $method->roles);
        $this->assertEquals(array(), $method->returnPermissions);
        $this->assertEquals(array(0 => array('VIEW')), $method->paramPermissions);

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooInterface'));
        $this->assertTrue(isset($metadata->methodMetadata['foo']));
        $method = $metadata->methodMetadata['foo'];
        $this->assertEquals(array(), $method->roles);
        $this->assertEquals(array(0 => array('OWNER'), 1 => array('EDIT')), $method->paramPermissions);
        $this->assertEquals(array('MASTER'), $method->returnPermissions);
    }

    public function testLoadMetadataFromClassWithShortNotation()
    {
        $driver = new AnnotationDriver();

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));
        $this->assertTrue(isset($metadata->methodMetadata['shortNotation']));
        $method = $metadata->methodMetadata['shortNotation'];
        $this->assertEquals(array('ROLE_FOO', 'ROLE_BAR'), $method->roles);
    }
}