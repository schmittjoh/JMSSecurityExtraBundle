<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization;

use JMS\SecurityExtraBundle\Security\Authorization\RememberingAccessDecisionManager;

class RememberingAccessDecisionManagerTest extends \PHPUnit\Framework\TestCase
{
    private $adm;
    private $delegate;

    public function testRemembersTheLastCall()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();

        $this->assertNull($this->adm->getLastDecisionCall());
        $this->delegate->expects($this->once())
            ->method('decide')
            ->with($token, array('FOO'), null)
            ->will($this->returnValue(true));

        $this->assertTrue($this->adm->decide($token, array('FOO')));
        $this->assertSame(array($token, array('FOO'), null), $this->adm->getLastDecisionCall());
    }

    public function testSupportsAttribute()
    {
        if (!method_exists('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface', 'supportsClass')) {
            return $this->markTestSkipped('Not available with sf 3.0.');
        }

        $this->delegate->expects($this->once())
            ->method('supportsAttribute')
            ->with('FOO')
            ->will($this->returnValue(false));

        $this->assertFalse($this->adm->supportsAttribute('FOO'));
    }

    public function testSupportsClass()
    {
        if(!method_exists('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface', 'supportsClass')) {
            return $this->markTestSkipped('Not available with sf 3.0.');
        }

        $this->delegate->expects($this->once())
            ->method('supportsClass')
            ->with('BAR')
            ->will($this->returnValue(true));

        $this->assertTrue($this->adm->supportsClass('BAR'));
    }

    protected function setUp()
    {
        $this->delegate = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface')->getMock();
        $this->adm = new RememberingAccessDecisionManager($this->delegate);
    }
}
