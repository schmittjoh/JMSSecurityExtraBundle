<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization\Interception;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManagerInterface;
use Bundle\JMS\SecurityExtraBundle\Security\Authorization\RunAsManagerInterface;
use Symfony\Component\Security\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\SecurityContext;
use Symfony\Component\Security\Exception\AccessDeniedException;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Exception\AuthenticationCredentialsNotFoundException;

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
 * All invocations of secure methods will go through this class.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
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

    public function invoke(SecureMethodInvocation $method, array $metadata)
    {
        $runAsToken = $this->beforeInvocation($method, $metadata);

        $toInvoke = $method->getDeclaringClass()->getParentClass()->getMethod($method->getName());
        if (true === $nonPublic = !$toInvoke->isPublic()) {
            $toInvoke->setAccessible(true);
        }

        // special processing of methods that return references
        if (true === $toInvoke->returnsReference()) {
            $returnValue = array();
            $returnValue[0] = &$toInvoke->invokeArgs($method->getThis(), $method->getArguments());
        } else {
            $returnValue = $toInvoke->invokeArgs($method->getThis(), $method->getArguments());
        }

        if ($nonPublic) {
            $toInvoke->setAccessible(false);
        }

        if (null === $runAsToken && 0 === count($metadata['return_permissions'])) {
            return $returnValue;
        }

        return $this->afterInvocation($method, $metadata, $runAsToken, $returnValue);
    }

    protected function beforeInvocation(SecureMethodInvocation $method, array $metadata)
    {
        if (null === $token = $this->securityContext->getToken()) {
            throw new AuthenticationCredentialsNotFoundException(
                'The security context was not populated with a Token.'
            );
        }

        $token = $this->authenticateIfRequired($token);

        if (count($metadata['roles']) > 0 && false === $this->accessDecisionManager->decide($token, $metadata['roles'], $method)) {
            throw new AccessDeniedException('Token does not have the required roles.');
        }

        if (count($metadata['param_permissions']) > 0) {
            foreach ($method->getArguments() as $index => $argument) {
                if (null !== $argument && isset($metadata['param_permissions'][$index]) && false === $this->accessDecisionManager->decide($token, $metadata['param_permissions'][$index], $argument)) {
                    throw new AccessDeniedException(sprintf('Token has not required permissions for method "%s::%s".', $method->getDeclaringClass()->getParentClass()->getName(), $method->getName()));
                }
            }
        }

        if (count($metadata['run_as_roles']) > 0) {
            $runAsToken = $this->runAsManager->buildRunAs($token, $method, $metadata['run_as_roles']);

            if (null !== $this->logger) {
                $this->logger->debug('Populating security context with RunAsToken');
            }

            $this->securityContext->setToken($runAsToken);
        } else {
            $runAsToken = null;
        }

        return $runAsToken;
    }

    protected function afterInvocation(SecureMethodInvocation $method, array $metadata, $runAsToken, $returnValue)
    {
        if (null !== $runAsToken) {
            if (null !== $this->logger) {
                $this->logger->debug('Populating security context with original Token.');
            }

            $this->securityContext->setToken($token = $runAsToken->getOriginalToken());
        } else {
            $token = $this->securityContext->getToken();
        }

        if (0 === count($metadata['return_permissions'])) {
            return $returnValue;
        }

        return $this->afterInvocationManager->decide($token, $method, $metadata['return_permissions'], $returnValue);
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