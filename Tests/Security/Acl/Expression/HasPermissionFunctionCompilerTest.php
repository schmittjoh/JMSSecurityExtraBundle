<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Acl\Expression;

use JMS\SecurityExtraBundle\Security\Acl\Expression\HasPermissionFunctionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class HasPermissionFunctionCompilerTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;
    
    public function testCompile()
    {
        $source = $this->compiler->compile(new FunctionExpression('hasPermission', 
            array(new ConstantExpression('VIEW'), new VariableExpression('foo'))));
        
        $this->assertContains(
        	"\$context['permission_evaluator']->hasPermission('VIEW', \$context['foo']);",
        	$source);
    }
    
    protected function setUp()
    {
        $this->compiler = new ExpressionCompiler();
        $this->compiler->addFunctionCompiler(new HasPermissionFunctionCompiler());
    }
}