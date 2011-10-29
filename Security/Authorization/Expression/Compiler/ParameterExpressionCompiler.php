<?php

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler;

use Symfony\Component\Security\Core\Authorization\Expression\Ast\VariableExpression;
use Symfony\Component\Security\Core\Authorization\Expression\ExpressionCompiler;
use Symfony\Component\Security\Core\Authorization\Expression\Ast\ExpressionInterface;
use Symfony\Component\Security\Core\Authorization\Expression\Compiler\TypeCompilerInterface;

class ParameterExpressionCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return 'Symfony\Component\Security\Core\Authorization\Expression\Ast\ParameterExpression';
    }

    public function compilePreconditions(ExpressionCompiler $compiler, ExpressionInterface $parameter)
    {
        $compiler->verifyItem('object', 'CG\Proxy\MethodInvocation');

        if (!isset($compiler->attributes['parameter_mapping_name'])) {
            $this->addParameterMapping($compiler);
        }

        $compiler
            ->writeln("if (!isset(\${$compiler->attributes['parameter_mapping_name']}['{$parameter->name}'])) {")
            ->indent()
            ->write("throw new RuntimeException(sprintf('There is no parameter with name \"{$parameter->name}\" for method \"%s\".', ")
            ->compileInternal(new VariableExpression('object'))
            ->writeln("));")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    public function compile(ExpressionCompiler $compiler, ExpressionInterface $parameter)
    {
        $compiler
            ->compileInternal(new VariableExpression('object'))
            ->write("->arguments[")
            ->write("\${$compiler->attributes['parameter_mapping_name']}")
            ->write("['{$parameter->name}']]")
        ;
    }

    private function addParameterMapping(ExpressionCompiler $compiler)
    {
        $name = $compiler->nextName();
        $indexName = $compiler->nextName();
        $paramName = $compiler->nextName();

        $compiler
            ->setAttribute('parameter_mapping_name', $name)
            ->writeln("\$$name = array();")
            ->write("foreach (")
            ->compileInternal(new VariableExpression('object'))
            ->writeln("->reflection->getParameters() as \$$indexName => \$$paramName) {")
            ->indent()
            ->writeln("\${$name}[\${$paramName}->name] = \$$indexName;")
            ->outdent()
            ->writeln("}\n")
        ;
    }
}