<?php

namespace JMS\SecurityExtraBundle\Security\Authorization\Interception;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
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

/**
 * This class holds all data which is associated with the invocation of a
 * method.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MethodInvocation extends \ReflectionMethod
{
    private $arguments;
    private $object;

    public function __construct($class, $name, $object, array $arguments = array())
    {
        parent::__construct($class, $name);

        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object.');
        }

        $this->arguments = $arguments;
        $this->object = $object;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Returns the arguments that were passed to the method
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns the object the method was being invoked on
     *
     * @return object
     */
    public function getThis()
    {
        return $this->object;
    }
}