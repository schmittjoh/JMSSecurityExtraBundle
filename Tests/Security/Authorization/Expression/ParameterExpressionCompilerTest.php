<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use CG\Proxy\MethodInvocation;

use Symfony\Component\Security\Core\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\ParameterExpressionCompiler;
use Symfony\Component\Security\Core\Authorization\Expression\ExpressionCompiler;

class ParameterExpressionCompilerTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;

    public function testCompile()
    {
        $evaluator = eval($source = $this->compiler->compileExpression(new Expression(
            '#foo == "bar"')));

        $object = new ParameterAccessTest;
        $reflection = new \ReflectionMethod($object, 'secure');
        $invocation = new MethodInvocation($reflection, $object, array('bar'), array());
        $this->assertTrue($evaluator(array('object' => $invocation)));

        $invocation->arguments = array('foo');
        $this->assertFalse($evaluator(array('object' => $invocation)));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCompileThrowsExceptionWhenNoMethodInvocation()
    {
        $evaluator = eval($this->compiler->compileExpression(new Expression(
            '#foo == "fofo"')));

        $evaluator(array('object' => new \stdClass));
    }

    protected function setUp()
    {
        if (!class_exists('Symfony\Component\Security\Core\Authorization\Expression\ExpressionCompiler')) {
            $this->markTestSkipped('The expression language is not available.');
        }

        $this->compiler = new ExpressionCompiler();
        $this->compiler->addTypeCompiler(new ParameterExpressionCompiler());
    }
}

class ParameterAccessTest
{
    public function secure($foo)
    {
    }
}