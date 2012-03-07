<?php

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

namespace JMS\SecurityExtraBundle\Security\Authorization\Interception;

use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManagerInterface;
use JMS\SecurityExtraBundle\Security\Authorization\RunAsManagerInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

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

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, AccessDecisionManagerInterface $accessDecisionManager,
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

    public function invoke(MethodInvocation $method, array $metadata)
    {
        $runAsToken = $this->beforeInvocation($method, $metadata);

        if (true === $nonPublic = !$method->isPublic()) {
            $method->setAccessible(true);
        }

        try {
            $returnValue = $method->invokeArgs($method->getThis(), $method->getArguments());

            if ($nonPublic) {
                $method->setAccessible(false);
            }

            if (null !== $runAsToken) {
                $this->restoreOriginalToken($runAsToken);
            }

            if (!$metadata['return_permissions']) {
                return $returnValue;
            }

            return $this->afterInvocation($method, $metadata, $runAsToken, $returnValue);
        } catch (\Exception $failed) {
            if ($nonPublic) {
                $method->setAccessible(false);
            }

            if (null !== $runAsToken) {
                $this->restoreOriginalToken($runAsToken);
            }

            throw $failed;
        }
    }

    protected function beforeInvocation(MethodInvocation $method, array $metadata)
    {
        if (null === $token = $this->securityContext->getToken()) {
            throw new AuthenticationCredentialsNotFoundException(
                'The security context was not populated with a Token.'
            );
        }

        if ($this->alwaysAuthenticate || !$token->isAuthenticated()) {
            $token = $this->authenticationManager->authenticate($token);
            $this->securityContext->setToken($token);
        }

        if ($metadata['roles'] && false === $this->accessDecisionManager->decide($token, $metadata['roles'], $method)) {
            throw new AccessDeniedException('Token does not have the required roles.');
        }

        if ($metadata['param_permissions']) {
            foreach ($method->getArguments() as $index => $argument) {
                if (null !== $argument && isset($metadata['param_permissions'][$index]) && false === $this->accessDecisionManager->decide($token, $metadata['param_permissions'][$index], $argument)) {
                    throw new AccessDeniedException(sprintf('Token does not have the required permissions for method "%s::%s".', $method->class, $method->getName()));
                }
            }
        }

        $runAsToken = null;
        if ($metadata['run_as_roles']) {
            $runAsToken = $this->runAsManager->buildRunAs($token, $method, $metadata['run_as_roles']);

            if (null !== $this->logger) {
                $this->logger->debug('Populating security context with RunAsToken');
            }

            if (null === $runAsToken) {
                throw new \RuntimeException('RunAsManager must not return null from buildRunAs().');
            }

            $this->securityContext->setToken($runAsToken);
        }

        return $runAsToken;
    }

    protected function afterInvocation(MethodInvocation $method, array $metadata, $runAsToken, $returnValue)
    {
        if (!$metadata['return_permissions']) {
            return $returnValue;
        }

        return $this->afterInvocationManager->decide($this->securityContext->getToken(), $method, $metadata['return_permissions'], $returnValue);
    }

    protected function restoreOriginalToken(RunAsUserToken $runAsToken)
    {
        if (null !== $this->logger) {
            $this->logger->debug('Populating security context with original Token.');
        }

        $this->securityContext->setToken($runAsToken->getOriginalToken());
    }
}