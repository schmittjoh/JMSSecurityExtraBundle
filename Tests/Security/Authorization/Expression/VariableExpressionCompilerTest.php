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

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class VariableExpressionCompilerTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;

    public function testCompile()
    {
        $evaluator = eval($source = $this->compiler->compileExpression(new Expression(
            'foo == "bar"')));

        $this->assertTrue($evaluator(array('foo' => 'bar')));

        $this->assertFalse($evaluator(array('foo' => 'baz')));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCompileThrowsExceptionWhenNoMethodInvocation()
    {
        $evaluator = eval($this->compiler->compileExpression(new Expression(
            'foo == "fofo"')));

        $evaluator(array());
    }

    protected function setUp()
    {
        $this->compiler = new ExpressionCompiler();
    }
}
