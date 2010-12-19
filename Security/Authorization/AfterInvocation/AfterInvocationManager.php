<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

class AfterInvocationManager implements AfterInvocationManagerInterface
{
    protected $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function decide(TokenInterface $token, $secureInvocation, array $attributes, $returnedObject)
    {
        foreach ($providers as $provider) {
            $returnedObject = $provider->decide($token, $secureInvocation, $attributes, $returnedObject);
        }

        return $returnedObject;
    }

    public function supportsAttribute($attribute)
    {
        foreach ($this->providers as $provider) {
            if (true === $provider->supportsAttribute($attribute)) {
                return true;
            }
        }

        return false;
    }

    public function supportsClass($className)
    {
        foreach ($this->providers as $provider) {
            if (true === $provider->supportsClass($className)) {
                return true;
            }
        }

        return false;
    }
}