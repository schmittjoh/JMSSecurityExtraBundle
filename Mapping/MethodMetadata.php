<?php

namespace Bundle\JMS\SecurityExtraBundle\Mapping;

/*
 * Copyright 2010 Johannes M. Schmitt
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
 * Contains method metadata information
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MethodMetadata
{
    protected $roles;
    protected $reflection;
    protected $paramPermissions;
    protected $returnPermissions;
    protected $runAsRoles;
    protected $satisfiesParentSecurityPolicy;

    public function __construct(\ReflectionMethod $method)
    {
        $this->reflection = $method;
        $this->roles = array();
        $this->paramPermissions = array();
        $this->returnPermissions = array();
        $this->runAsRoles = array();
        $this->satisfiesParentSecurityPolicy = false;
    }

    /**
     * Adds a parameter restriction
     *
     * @param integer $index 0-based
     * @param array $permissions
     */
    public function addParamPermissions($index, array $permissions)
    {
        $this->paramPermissions[$index] = $permissions;
    }

    public function addReturnPermissions(array $permissions)
    {
        $this->returnPermissions = $permissions;
    }

    public function satisfiesParentSecurityPolicy()
    {
        return $this->satisfiesParentSecurityPolicy;
    }

    public function getParamPermissions()
    {
        return $this->paramPermissions;
    }

    public function getReturnPermissions()
    {
        return $this->returnPermissions;
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getRunAsRoles()
    {
        return $this->runAsRoles;
    }

    public function isDeclaredOnInterface()
    {
        $name = $this->reflection->getName();
        foreach ($this->reflection->getDeclaringClass()->getInterfaces() as $interface) {
            if ($interface->hasMethod($name)) {
                return true;
            }
        }

        return false;
    }

    public function setReturnPermissions(array $permissions)
    {
        $this->returnPermissions = $permissions;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function setRunAsRoles(array $roles)
    {
        $this->runAsRoles = $roles;
    }

    public function setSatisfiesParentSecurityPolicy()
    {
        $this->satisfiesParentSecurityPolicy = true;
    }

    /**
     * This allows to merge in metadata from an interface
     *
     * @param MethodMetadata $method
     * @return void
     */
    public function merge(MethodMetadata $method)
    {
        if (0 === count($this->roles)) {
            $this->roles = $method->getRoles();
        }

        if (0 === count($this->returnPermissions)) {
            $this->returnPermissions = $method->getReturnPermissions();
        }

        if (0 === count($this->runAsRoles)) {
            $this->runAsRoles = $method->getRunAsRoles();
        }

        foreach ($method->getParamPermissions() as $index => $permissions) {
            if (!isset($this->paramPermissions[$index])) {
                $this->paramPermissions[$index] = $permissions;
            }
        }
    }
}