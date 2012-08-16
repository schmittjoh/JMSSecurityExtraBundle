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

namespace JMS\SecurityExtraBundle\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\NotExpression;

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\IsEqualExpression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ParameterExpression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\OrExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\AndExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ArrayExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetItemExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetPropertyExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\MethodCallExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ExpressionInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;

final class ExpressionParser
{
    const PRECEDENCE_OR       = 10;
    const PRECEDENCE_AND      = 15;
    const PRECEDENCE_IS_EQUAL = 20;
    const PRECEDENCE_NOT      = 30;

    private $lexer;

    public function __construct()
    {
        $this->lexer = new ExpressionLexer();
    }

    public function parse($str)
    {
        $this->lexer->initialize($str);

        $expr = $this->Expression();

        if (null !== $this->lexer->lookahead) {
            throw new \RuntimeException(sprintf('Malformed expression. Expected end of expression, but got "%s" (%s).',
                $this->lexer->lookahead['value'], $this->lexer->getLiteral($this->lexer->lookahead['type'])));
        }

        return $expr;
    }

    private function Expression($precedence = 0)
    {
        $expr = $this->Primary();

        while (true) {
            if (ExpressionLexer::T_AND === $this->lexer->lookahead['type']
                    && $precedence <= self::PRECEDENCE_AND) {
                $this->lexer->next();

                $expr = new AndExpression($expr, $this->Expression(
                    self::PRECEDENCE_AND + 1));
                continue;
            }

            if (ExpressionLexer::T_OR === $this->lexer->lookahead['type']
                    && $precedence <= self::PRECEDENCE_OR) {
                $this->lexer->next();

                $expr = new OrExpression($expr, $this->Expression(
                    self::PRECEDENCE_OR + 1));
                continue;
            }

            if (ExpressionLexer::T_IS_EQUAL === $this->lexer->lookahead['type']
                    && $precedence <= self::PRECEDENCE_IS_EQUAL) {
                $this->lexer->next();

                $expr = new IsEqualExpression($expr, $this->Expression(
                    self::PRECEDENCE_IS_EQUAL + 1));
                continue;
            }

            break;
        }

        return $expr;
    }

    private function Primary()
    {
        if (ExpressionLexer::T_NOT === $this->lexer->lookahead['type']) {
            $this->lexer->next();
            $expr = new NotExpression($this->Expression(self::PRECEDENCE_NOT));

            return $this->Suffix($expr);
        }

        if (ExpressionLexer::T_OPEN_PARENTHESIS === $this->lexer->lookahead['type']) {
            $this->lexer->next();
            $expr = $this->Expression();
            $this->match(ExpressionLexer::T_CLOSE_PARENTHESIS);

            return $this->Suffix($expr);
        }

        if (ExpressionLexer::T_STRING === $this->lexer->lookahead['type']) {
            return new ConstantExpression($this->match(ExpressionLexer::T_STRING));
        }

        if (ExpressionLexer::T_OPEN_BRACE === $this->lexer->lookahead['type']) {
            return $this->Suffix($this->MapExpr());
        }

        if (ExpressionLexer::T_OPEN_BRACKET === $this->lexer->lookahead['type']) {
            return $this->Suffix($this->ListExpr());
        }

        if (ExpressionLexer::T_IDENTIFIER === $this->lexer->lookahead['type']) {
            $name = $this->match(ExpressionLexer::T_IDENTIFIER);

            if (ExpressionLexer::T_OPEN_PARENTHESIS === $this->lexer->lookahead['type']) {
                $args = $this->Arguments();

                return $this->Suffix(new FunctionExpression($name, $args));
            }

            return $this->Suffix(new VariableExpression($name));
        }

        if (ExpressionLexer::T_PARAMETER === $this->lexer->lookahead['type']) {
            return $this->Suffix(new ParameterExpression($this->match(ExpressionLexer::T_PARAMETER)));
        }

        $this->error('primary expression');
    }

    private function ListExpr()
    {
        $this->match(ExpressionLexer::T_OPEN_BRACKET);

        $elements = array();
        while (ExpressionLexer::T_CLOSE_BRACKET !== $this->lexer->lookahead['type']) {
            $elements[] = $this->Expression();

            if (ExpressionLexer::T_COMMA !== $this->lexer->lookahead['type']) {
                break;
            }
            $this->lexer->next();
        }

        $this->match(ExpressionLexer::T_CLOSE_BRACKET);

        return new ArrayExpression($elements);
    }

    private function MapExpr()
    {
        $this->match(ExpressionLexer::T_OPEN_BRACE);

        $entries = array();
        while (ExpressionLexer::T_CLOSE_BRACE !== $this->lexer->lookahead['type']) {
            $key = $this->match(ExpressionLexer::T_STRING);
            $this->match(ExpressionLexer::T_COLON);
            $entries[$key] = $this->Expression();

            if (ExpressionLexer::T_COMMA !== $this->lexer->lookahead['type']) {
                break;
            }

            $this->lexer->next();
        }

        $this->match(ExpressionLexer::T_CLOSE_BRACE);

        return new ArrayExpression($entries);
    }

    private function Suffix(ExpressionInterface $expr)
    {
        while (true) {
            if (ExpressionLexer::T_OBJECT_OPERATOR === $this->lexer->lookahead['type']) {
                $this->lexer->next();
                $name = $this->match(ExpressionLexer::T_IDENTIFIER);

                if (ExpressionLexer::T_OPEN_PARENTHESIS === $this->lexer->lookahead['type']) {
                    $args = $this->Arguments();
                    $expr = new MethodCallExpression($expr, $name, $args);
                    continue;
                }

                $expr = new GetPropertyExpression($expr, $name);
                continue;
            }

            if (ExpressionLexer::T_OPEN_BRACKET === $this->lexer->lookahead['type']) {
                $this->lexer->next();
                $key = $this->Expression();
                $this->match(ExpressionLexer::T_CLOSE_BRACKET);
                $expr = new GetItemExpression($expr, $key);
                continue;
            }

            break;
        }

        return $expr;
    }

    private function FunctionCall()
    {
        $name = $this->match(ExpressionLexer::T_IDENTIFIER);
        $args = $this->Arguments();

        return new FunctionExpression($name, $args);
    }

    private function Arguments()
    {
        $this->match(ExpressionLexer::T_OPEN_PARENTHESIS);
        $args = array();

        while (ExpressionLexer::T_CLOSE_PARENTHESIS !== $this->lexer->lookahead['type']) {
            $args[] = $this->Expression();

            if (ExpressionLexer::T_COMMA !== $this->lexer->lookahead['type']) {
                break;
            }

            $this->match(ExpressionLexer::T_COMMA);
        }
        $this->match(ExpressionLexer::T_CLOSE_PARENTHESIS);

        return $args;
    }

    private function Value()
    {
        return $this->matchAny(array(ExpressionLexer::T_STRING));
    }

    private function matchAny(array $types)
    {
        if (null !== $this->lexer->lookahead) {
            foreach ($types as $type) {
                if ($type === $this->lexer->lookahead['type']) {
                    $this->lexer->next();

                    return $this->lexer->token['value'];
                }
            }
        }

        $this->error(sprintf('one of these tokens "%s"',
            implode('", "', array_map(array('JMS\SecurityExtraBundle\Security\Authorization\Expression\Lexer', 'getLiteral'), $types))
        ));
    }

    private function match($type)
    {
        if (null === $this->lexer->lookahead
            || $type !== $this->lexer->lookahead['type']) {
            $this->error(sprintf('token "%s"', ExpressionLexer::getLiteral($type)));
        }

        $this->lexer->next();

        return $this->lexer->token['value'];
    }

    private function error($expected)
    {
        $actual = null === $this->lexer->lookahead ? 'end of file'
            : sprintf('token "%s" with value "%s" at position %d',
            ExpressionLexer::getLiteral($this->lexer->lookahead['type']),
            $this->lexer->lookahead['value'],
            $this->lexer->lookahead['position']);

        throw new RuntimeException(sprintf('Expected %s, but got %s.', $expected, $actual));
    }
}
