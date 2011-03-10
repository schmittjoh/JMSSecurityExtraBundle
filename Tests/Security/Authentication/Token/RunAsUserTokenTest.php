<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authentication\Token;

use Symfony\Component\Security\Core\Role\Role;
use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

class RunAsUserTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $runAsToken = new RunAsUserToken('foo', $user, 'secret', array('ROLE_FOO'), $token);
        $this->assertSame($user, $runAsToken->getUser());
        $this->assertSame('secret', $runAsToken->getCredentials());
        $this->assertSame($token, $runAsToken->getOriginalToken());
        $this->assertEquals(array(new Role('ROLE_FOO')), $runAsToken->getRoles());
        $this->assertSame('foo', $runAsToken->getKey());
    }

    public function testEraseCredentials()
    {
        $token = new RunAsUserToken('foo', 'foo', 'secret', array(), $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface'));
        $this->assertEquals('secret', $token->getCredentials());
        $token->eraseCredentials();
        $this->assertNull($token->getCredentials());
    }
}