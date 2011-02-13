<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception;

use JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodInvocation;

class MethodInvocationTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $service = new Service();
        $reflection = new MethodInvocation('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\Service', 'foo', $service, array('foo'));

        $this->assertInstanceOf('\ReflectionMethod', $reflection);
        $this->assertSame($service, $reflection->getThis());
        $this->assertSame(array('foo'), $reflection->getArguments());
    }
}

class Service {
    public function foo($param) { }
}