<?php

namespace Bundle\JMS\SecurityExtraBundle\Authorization;

class SecureMethodInvocation extends \ReflectionMethod
{
    protected $arguments;
    protected $object;

    public function __construct($object, $name, array $arguments = array())
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object.');
        }
        parent::__construct($object, $name);

        $this->arguments = $arguments;
        $this->object = $object;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getThis()
    {
        return $this->object;
    }
}