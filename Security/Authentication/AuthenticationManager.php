<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authentication;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Exception\ProviderNotFoundException;

/**
 * A wrapper around all provider managers (more a workaround which is currently
 * necessary).
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AuthenticationManager implements AuthenticationManagerInterface
{
    protected $managers;

    public function __construct(array $managers)
    {
        $this->managers = $managers;
    }

    public function authenticate(TokenInterface $token)
    {
        foreach ($this->managers as $manager) {
            try {
                return $manager->authenticate($token);
            } catch (ProviderNotFoundException $notFound) {
                // try next one
            }
        }

        throw new ProviderNotFoundException(sprintf('No provider found for token "%s".', get_class($token)));
    }
}