<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization;

use Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

use Symfony\Component\Security\Role\Role;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

class RunAsManager implements RunAsManagerInterface
{
    protected $key;
    protected $rolePrefix;

    public function __construct($key, $rolePrefix = 'ROLE_')
    {
        $this->key = $key;
        $this->rolePrefix = $rolePrefix;
    }

    public function buildRunAs(TokenInterface $token, $secureObject, array $attributes)
    {
        $roles = array();
        foreach ($attributes as $attribute)
        {
            if ($this->supportsAttribute($attribute)) {
                $roles[] = new Role($attribute);
            }
        }

        if (0 === count($roles)) {
            return null;
        }

        $roles = array_merge($roles, $token->getRoles());

        return new RunAsUserToken($this->key, $token->getUser(), $token->getCredentials(), $roles, $token);
    }

    public function supportsAttribute($attribute)
    {
        return !empty($attribute) && 0 === strpos($attribute, $this->rolePrefix);
    }

    public function supportsClass($className)
    {
        return true;
    }
}