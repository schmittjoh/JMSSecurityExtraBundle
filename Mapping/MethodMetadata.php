<?php

namespace Bundle\JMS\SecurityExtraBundle\Mapping;

class MethodMetadata
{
    protected $roles;
    protected $reflection;
    protected $paramPermissions;
    protected $returnPermissions;

    public function __construct(\ReflectionMethod $method)
    {
        $this->reflection = $method;
        $this->roles = array();
        $this->paramPermissions = array();
        $this->returnPermissions = array();
    }

    public function addParamPermissions($name, array $permissions)
    {
        $this->paramPermissions[$name] = $permissions;
    }

    public function addReturnPermissions(array $permissions)
    {
        $this->returnPermissions = $permissions;
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

    public function setReturnPermissions(array $permissions)
    {
        $this->returnPermissions = $permissions;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function merge(MethodMetadata $metadata)
    {
        if (count($roles = $metadata->getRoles()) > 0) {
            $this->roles = $roles;
        }

        if (count($permissions = $metadata->getReturnPermissions()) > 0) {
            $this->returnPermissions = $permissions;
        }

        foreach ($metadata->getParamPermissions() as $name => $permissions) {
            $this->paramPermissions[$name] = $permissions;
        }
    }
}