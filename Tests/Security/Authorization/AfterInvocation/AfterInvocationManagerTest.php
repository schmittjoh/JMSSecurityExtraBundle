<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Security\Authorization\AfterInvocation;

use Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManager;

class AfterInvocationManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testDecide()
    {
        $attributes = array('FOO');

        $provider1 = $this->getProvider();
        $provider1
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->isInstanceOf('Symfony\Component\Security\Authentication\Token\TokenInterface'),
                $this->anything(),
                $this->equalTo($attributes),
                $this->equalTo('foo')
            )
            ->will($this->returnValue('bar'))
        ;

        $provider2 = $this->getProvider();
        $provider2
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->isInstanceOf('Symfony\Component\Security\Authentication\Token\TokenInterface'),
                $this->anything(),
                $this->equalTo($attributes),
                $this->equalTo('bar')
            )
            ->will($this->returnValue('moo'))
        ;

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $manager = new AfterInvocationManager(array($provider1, $provider2));
        $this->assertEquals('moo', $manager->decide($token, 'sth', $attributes, 'foo'));
    }

    /**
     * @dataProvider getSupportsTests
     */
    public function testSupportsAttribute($attribute, $supported)
    {
        $provider = $this->getProvider();
        $provider
            ->expects($this->once())
            ->method('supportsAttribute')
            ->with($this->equalTo($attribute))
            ->will($this->returnValue($supported))
        ;

        $manager = new AfterInvocationManager(array($provider));
        $this->assertSame($supported, $manager->supportsAttribute($attribute));
    }

    /**
     * @dataProvider getSupportsTests
     */
    public function testSupportsClass($class, $supported)
    {
        $provider = $this->getProvider();
        $provider
            ->expects($this->once())
            ->method('supportsClass')
            ->with($this->equalTo($class))
            ->will($this->returnValue($supported))
        ;

        $manager = new AfterInvocationManager(array($provider));
        $this->assertSame($supported, $manager->supportsClass($class));
    }

    public function getSupportsTests()
    {
        return array(
            array('FOO', true),
            array('BAR', false),
        );
    }

    protected function getProvider()
    {
        return $this->getMock('Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationProviderInterface');
    }
}