<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception;

use Bundle\JMS\SecurityExtraBundle\Security\Authorization\Interception\SecureMethodInvocation;

class SecureMethodInvocationTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $service = new SubService();
        $reflection = new SecureMethodInvocation($service, 'foo', array('foo'));

        $this->assertInstanceOf('\ReflectionMethod', $reflection);
        $this->assertSame($service, $reflection->getThis());
        $this->assertSame(array('foo'), $reflection->getArguments());
    }
}

class Service {
    public function foo($param) { }
}

class SubService extends Service
{

}