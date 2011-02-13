<?php

namespace JMS\SecurityExtraBundle\Mapping\Driver;

use Doctrine\Common\Annotations\Lexer;
use Doctrine\Common\Annotations\Parser;

class AnnotationParser extends Parser
{
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