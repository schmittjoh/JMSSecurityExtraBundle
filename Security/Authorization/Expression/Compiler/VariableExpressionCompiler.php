<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ExpressionInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class VariableExpressionCompiler implements TypeCompilerInterface
{
    public function getType()
    {
        return 'JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression';
    }

    public function compilePreconditions(ExpressionCompiler $compiler, ExpressionInterface $expr)
    {
        if ('user' === $expr->name) {
            $compiler
                ->setAttribute('user_var_name', $name = $compiler->nextName())
                ->write("\$$name = ")
                ->compileInternal(new VariableExpression('token'))
                ->write("->getUser();\n\n")
            ;
        }
    }

    public function compile(ExpressionCompiler $compiler, ExpressionInterface $expr)
    {
        if ('permitAll' === $expr->name) {
            $compiler->write('true');

            return;
        }

        if ('denyAll' === $expr->name) {
            $compiler->write('false');

            return;
        }

        if ('user' === $expr->name) {
            $compiler->write("\${$compiler->attributes['user_var_name']}");

            return;
        }

        if ($expr->allowNull) {
            $compiler->write("(isset(\$context['{$expr->name}']) ? ");
        }

        $compiler->write("\$context['{$expr->name}']");

        if ($expr->allowNull) {
            $compiler->write(" : null)");
        }
    }
}