<?php

namespace Bundle\JMS\SecurityExtraBundle\Annotation;

class SecureParam implements AnnotationInterface
{
    public $name;
    public $permissions;
}