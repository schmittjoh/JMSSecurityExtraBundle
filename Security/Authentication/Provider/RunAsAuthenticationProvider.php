<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Exception\BadCredentialsException;

use Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

use Symfony\Component\Security\Authentication\Provider\AuthenticationProviderInterface;

class RunAsAuthenticationProvider implements AuthenticationProviderInterface
{
    protected $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        if ($token->getKey() === $this->key) {
            return $token;
        } else {
            throw new BadCredentialsException('The keys do not match.');
        }
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof RunAsUserToken;
    }
}