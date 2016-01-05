<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Util;

use JMS\SecurityExtraBundle\Security\Util\String;

class StringTest extends \PHPUnit_Framework_TestCase
{
    public function testEquals()
    {
        if(PHP_VERSION_ID > 70000) {
            return $this->markTestSkipped('String class name can\'t be used on php 7.');
        }
        $this->assertTrue(String::equals('password', 'password'));
        $this->assertFalse(String::equals('password', 'foo'));
    }
}
