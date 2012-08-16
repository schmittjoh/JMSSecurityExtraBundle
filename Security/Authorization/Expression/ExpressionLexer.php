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

use JMS\SecurityExtraBundle\Exception\InvalidArgumentException;

final class ExpressionLexer
{
    public $token;
    public $lookahead;

    private $tokens;
    private $pointer;

    const T_STRING = 1;
    const T_IDENTIFIER = 2;
    const T_NONE = 3;
    const T_COMMA = 4;
    const T_OPEN_PARENTHESIS = 5;
    const T_CLOSE_PARENTHESIS = 6;
    const T_AND = 7;
    const T_OR = 8;
    const T_PARAMETER = 9;
    const T_OBJECT_OPERATOR = 10;
    const T_OPEN_BRACKET = 11;
    const T_CLOSE_BRACKET = 12;
    const T_OPEN_BRACE = 13;
    const T_CLOSE_BRACE = 14;
    const T_COLON = 15;
    const T_IS_EQUAL = 16;
    const T_NOT = 17;

    public static function getLiteral($type)
    {
        static $constants;

        if (null === $constants) {
            $ref = new \ReflectionClass(get_called_class());
            $constants = $ref->getConstants();
        }

        if (false === $literal = array_search($type, $constants, true)) {
            throw new InvalidArgumentException(sprintf('There is no token of value "%s".', $type));
        }

        return $literal;
    }

    public function initialize($input)
    {
        static $pattern = '/(#?[a-z][a-z0-9]*|\'(?:[^\']|(?<=\\\\)\')*\'|"(?:[^"]|(?<=\\\\)")*"|&&|\|\||==)|\s+|(.)/i';

        $parts = preg_split($pattern, $input, -1, PREG_SPLIT_OFFSET_CAPTURE
            | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $tokens = array();
        foreach ($parts as $part) {
            list($value, $position) = $part;
            $type = self::T_NONE;

            if ("'" === $value[0] || '"' === $value[0]) {
                $type = self::T_STRING;
                $value = substr($value, 1, -1);
            } elseif (',' === $value) {
                $type = self::T_COMMA;
            } elseif ('(' === $value) {
                $type = self::T_OPEN_PARENTHESIS;
            } elseif (')' === $value) {
                $type = self::T_CLOSE_PARENTHESIS;
            } elseif ('[' === $value) {
                $type = self::T_OPEN_BRACKET;
            } elseif (']' === $value) {
                $type = self::T_CLOSE_BRACKET;
            } elseif ('{' === $value) {
                $type = self::T_OPEN_BRACE;
            } elseif ('}' === $value) {
                $type = self::T_CLOSE_BRACE;
            } elseif ('&&' === $value || 'and' === strtolower($value)) {
                $type = self::T_AND;
            } elseif ('||' === $value || 'or' === strtolower($value)) {
                $type = self::T_OR;
            } elseif ('!' === $value || 'not' === strtolower($value)) {
                $type = self::T_NOT;
            } elseif (':' === $value) {
                $type = self::T_COLON;
            } elseif ('.' === $value) {
                $type = self::T_OBJECT_OPERATOR;
            } elseif ('==' === $value) {
                $type = self::T_IS_EQUAL;
            } elseif ('#' === $value[0]) {
                $type = self::T_PARAMETER;
                $value = substr($value, 1);
            } elseif (ctype_alpha($value)) {
                $type = self::T_IDENTIFIER;
            }

            $tokens[] = array(
                'type'  => $type,
                'value' => $value,
                'position' => $position,
            );
        }

        $this->tokens = $tokens;
        $this->pointer = -1;
        $this->next();
    }

    public function next()
    {
        $this->pointer += 1;
        $this->token = $this->lookahead;
        $this->lookahead = isset($this->tokens[$this->pointer]) ?
            $this->tokens[$this->pointer] : null;

        return $this->lookahead !== null;
    }
}
