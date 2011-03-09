<?php

namespace JMS\SecurityExtraBundle\Annotation;

class RunAs implements AnnotationInterface
{
    private $roles;

    public function __construct(array $values)
    {
        if (!isset($values['roles'])) {
            throw new \InvalidArgumentException('"roles" must be defined for RunAs annotation.');
        }

        $this->roles = array_map('trim', explode(',', $values['roles']));
    }

    public function getRoles()
    {
        return $this->roles;
    }
}