<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Acl\Expression;

use JMS\SecurityExtraBundle\Security\Acl\Expression\HasClassPermissionFunctionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;
use PHPUnit\Framework\TestCase;

class HasClassPermissionFunctionCompilerTest extends TestCase
{
    private $compiler;

    public function testCompile()
    {
        $source = $this->compiler->compile(new FunctionExpression('hasClassPermission',
            array(new VariableExpression('foo'), new ConstantExpression('VIEW'))));

        $this->assertContains(
            "\$context['permission_evaluator']->hasPermission(\$context['token'], new Symfony\\Component\\Security\\Acl\\Domain\\ObjectIdentity(\$context['foo'], -1), 'VIEW');",
            $source);
    }

    public function testCompileUpperCasesPermissions()
    {
        $source = $this->compiler->compile(new FunctionExpression('hasClassPermission',
            array(new VariableExpression('foo'), new ConstantExpression('view'))));

        $this->assertContains(
            "\$context['permission_evaluator']->hasPermission(\$context['token'], new Symfony\\Component\\Security\\Acl\\Domain\\ObjectIdentity(\$context['foo'], -1), 'VIEW');",
            $source);
    }

    protected function setUp()
    {
        $this->compiler = new ExpressionCompiler();
        $this->compiler->addFunctionCompiler(new HasClassPermissionFunctionCompiler());
    }
}