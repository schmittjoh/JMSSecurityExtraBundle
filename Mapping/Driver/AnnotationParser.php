<?php

namespace JMS\SecurityExtraBundle\Mapping\Driver;

use Doctrine\Common\Annotations\Lexer;
use Doctrine\Common\Annotations\Parser;

class AnnotationParser extends Parser
{
    private static $strippedTags = array(
        "{@internal", "{@inheritdoc", "{@link"
    );

    /**
     * Parses the given docblock string for annotations.
     *
     * @param string $docBlockString The docblock string to parse.
     * @param string $context The parsing context.
     * @return array Array of annotations. If no annotations are found, an empty array is returned.
     */
    public function parse($docBlockString, $context='')
    {
        // Strip out some known inline tags.
        $input = str_replace(self::$strippedTags, '', $docBlockString);

        // Cut of the beginning of the input until the first '@'.
        if (!preg_match('/^\s*\*\s*(@.*)/ms', $input, $match)) {
            return array();
        }

        return parent::parse($match[1], $context);
    }

    /**
     * Annotations ::= Annotation {[ "*" ]* [Annotation]}*
     *
     * @return array
     */
    public function Annotations()
    {
        $this->isNestedAnnotation = false;

        $annotations = array();
        $annot = $this->Annotation();

        if ($annot !== false) {
            $annotations[] = $annot;
            $this->getLexer()->skipUntil(Lexer::T_AT);
        }

        while ($this->getLexer()->lookahead !== null && $this->getLexer()->isNextToken(Lexer::T_AT)) {
            $this->isNestedAnnotation = false;
            $annot = $this->Annotation();

            if ($annot !== false) {
                $annotations[] = $annot;
                $this->getLexer()->skipUntil(Lexer::T_AT);
            }
        }

        return $annotations;
    }
}