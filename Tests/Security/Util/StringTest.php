<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Util;

use JMS\SecurityExtraBundle\Security\Util\Text as TextUtil;
use PHPUnit\Framework\TestCase;

class StringTest extends TestCase
{
    public function testEquals()
    {
        $this->assertTrue(TextUtil::equals('password', 'password'));
        $this->assertFalse(TextUtil::equals('password', 'foo'));
    }
}
