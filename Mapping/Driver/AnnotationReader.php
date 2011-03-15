<?php

namespace JMS\SecurityExtraBundle\Mapping\Driver;

use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationReader as BaseAnnotationReader;

class AnnotationReader extends BaseAnnotationReader
{
    public function __construct(Cache $cache = null, Parser $parser = null)
    {
        if (null === $parser) {
            $parser = new AnnotationParser();
        }

        parent::__construct($cache, $parser);

        $this->setAutoloadAnnotations(false);
        $this->setDefaultAnnotationNamespace('JMS\\SecurityExtraBundle\\Annotation\\');

        $finder = new Finder();
        $finder
            ->name('*.php')
            ->in(__DIR__.'/../../Annotation/')
        ;
        foreach ($finder as $annotationFile) {
            require_once $annotationFile->getPathName();
        }
    }
}