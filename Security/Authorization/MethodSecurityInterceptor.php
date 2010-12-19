<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManagerInterface;

use Symfony\Component\Security\Authorization\AccessDecisionManagerInterface;

use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;

use Symfony\Component\Security\SecurityContext;

use Symfony\Component\Security\Exception\AccessDeniedException;
use Bundle\JMS\SecurityExtraBundle\Authorization\SecureMethodInvocation;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Exception\AuthenticationCredentialsNotFoundException;

class MethodSecurityInterceptor
{
    protected $alwaysAuthenticate;
    protected $securityContext;
    protected $authenticationManager;
    protected $accessDecisionManager;
    protected $afterInvocationManager;
    protected $runAsManager;
    protected $logger;

    public function __construct(SecurityContext $securityContext, AuthenticationManagerInterface $authenticationManager, AccessDecisionManagerInterface $accessDecisionManager,
                                AfterInvocationManagerInterface $afterInvocationManager, RunAsManagerInterface $runAsManager, LoggerInterface $logger = null)
    {
        $this->alwaysAuthenticate = false;
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->afterInvocationManager = $afterInvocationManager;
        $this->runAsManager = $runAsManager;
        $this->logger = $logger;
    }

    public function setAlwaysAuthenticate($boolean)
    {
        $this->alwaysAuthenticate = !!$boolean;
    }

    public function beforeInvocation(SecureMethodInvocation $method, array $roles, array $paramPermissions, array $runAs = array())
    {
        if (null === $token = $this->securityContext->getToken()) {
            throw new AuthenticationCredentialsNotFoundException(
                'The security context was not populated with a Token.'
            );
        }

        $token = $this->authenticateIfRequired($token);

        if (count($roles) > 0 && false === $this->accessDecisionManager->decide($token, $method, $roles)) {
            throw new AccessDeniedException('Token does not have the required roles.');
        }

        if (count($paramPermissions) > 0) {
            $arguments = $method->getArguments();
            foreach ($paramPermissions as $index => $permissions) {
                if (null !== $arguments[$index] && false === $this->accessDecisionManager->decide($token, $arguments[$index], $permissions)) {
                    throw new AccessDeniedException(sprintf('Token has not required permissions for method "%s::%s".', $method->getDeclaringClass()->getParentClass()->getName(), $method->getName()));
                }
            }
        }

        if (count($runAs) > 0) {
            $runAsToken = $this->runAsManager->buildRunAs($token, $method, $runAs);

            if (null !== $this->logger) {
                $this->logger->debug('Populating security context with RunAsToken');
            }

            $this->securityContext->setToken($runAsToken);
        } else {
            $runAsToken = null;
        }

        return $runAsToken;
    }

    public function afterInvocation(SecureMethodInvocation $method, $returnedObject, TokenInterface $runAsToken, array $returnPermissions)
    {
        if (null !== $runAsToken) {
            if (null !== $this->logger) {
                $this->logger->debug('Populating security context with original Token.');
            }

            $this->securityContext->setToken($token = $runAsToken->getOriginalToken());
        } else {
            $token = $this->securityContext->getToken();
        }

        if (0 === count($returnPermissions)) {
            return $returnedObject;
        }

        return $this->afterInvocationManager->decide($token, $method, $returnPermissions, $returnedObject);
    }

    protected function authenticateIfRequired(TokenInterface $token)
    {
        if ($token->isAuthenticated() && !$this->alwaysAuthenticate) {
            return $token;
        }

        $token = $this->authenticationManager->authenticate($token);
        $this->securityContext->setToken($token);

        return $token;
    }
}