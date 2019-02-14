<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionLexer;
use PHPUnit\Framework\TestCase;

class ExpressionLexerTest extends TestCase
{
    private $lexer;

    public function testParameter()
    {
        $this->lexer->setInput('#contact');

        $this->assertEquals(array(
            'contact',
            0,
            ExpressionLexer::T_PARAMETER,
        ), $this->lexer->next);
        $this->assertFalse($this->lexer->moveNext());
    }

    protected function setUp()
    {
        $this->lexer = new ExpressionLexer();
    }
}