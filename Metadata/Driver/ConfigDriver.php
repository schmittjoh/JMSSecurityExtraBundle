<?php

namespace JMS\SecurityExtraBundle\Metadata\Driver;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use JMS\SecurityExtraBundle\Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;

/**
 * Uses Symfony2 DI configuration for metadata.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ConfigDriver extends AbstractDriver
{
    private $bundles;
    private $config;

    /**
     * @param array $bundles A list of used bundles indexed by name.
     * @param array $config  Metadata configuration
     */
    public function __construct(array $bundles, array $config)
    {
        uasort($bundles, function($operandA, $operandB) {
            return strlen($operandB) - strlen($operandA);
        });

        foreach ($bundles as $name => $namespace) {
            $bundles[$name] = substr($namespace, 0, strrpos($namespace, '\\'));
        }

        $this->bundles = $bundles;

        /** This is a BC layer */
        $this->config = array();
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $this->config[$key] = array(
                    'pre_authorize' => $value
                );
            } else {
                $this->config[$key] = $value;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getMethodScopeMetadata(\ReflectionMethod $method)
    {
        $configurationFound = null;

        if (null !== $notation = $this->getControllerNotation($method)) {
            $configurationFound = $this->getConfigForSignature($notation);
        }

        if (null === $configurationFound) {
            $configurationFound = $this->getConfigForSignature($method->class.'::'.$method->name);
        }

        return $configurationFound ? $configurationFound : array();
    }

    /**
     * {@inheritDoc}
     */
    protected function getClassScopeMetadata(\ReflectionClass $class)
    {
        /**
         * Class configuration metadata is not supported.  Use more general
         * regex pattern in configuration instead.
         */

        return array();
    }

    /**
     * {@inheritDoc}
     */
    protected function fromMetadataConfig(\ReflectionMethod $method, array $configs)
    {
        $parameters = array();
        foreach ($method->getParameters() as $index => $parameter) {
            $parameters[$parameter->getName()] = $index;
        }

        $methodMetadata = new MethodMetadata($method->class, $method->name);

        $hasSecurityMetadata = false;

        foreach ($configs['method'] as $name => $config) {
            switch ($name) {
                case "pre_authorize":
                    $methodMetadata->roles = array(new Expression($config));
                    $hasSecurityMetadata = true;
                    break;
                case "secure":
                    $methodMetadata->roles = $config['roles'];
                    $hasSecurityMetadata =  true;
                    break;
                case "secure_param":
                    $this->assertParamExistsForMethod($parameters, $config['name'], $method->name);
                    $methodMetadata->addParamPermissions(
                        $parameters[$config['name']], $config['permissions']
                    );
                    $hasSecurityMetadata = true;
                    break;
                case "secure_return":
                    $methodMetadata->returnPermissions = $config['permissions'];
                    $hasSecurityMetadata = true;
                    break;
                case "run_as":
                    $methodMetadata->runAsRoles = $config['roles'];
                    $hasSecurityMetadata = true;
                    break;
                case "satisfies_parent_security_policy":
                    $methodMetadata->satisfiesParentSecurityPolicy = true;
                    $hasSecurityMetadata = true;
                    break;
            }
        }

        return $hasSecurityMetadata ? $methodMetadata : null;
    }

    protected function getConfigForSignature($signature)
    {
        $configurationFound = null;

        foreach ($this->config as $pattern => $config) {
            if (!preg_match('#'.$pattern.'#i', $signature)) {
                continue;
            }

            $configurationFound = $config;

            break;
        }

        return $configurationFound;
    }

    protected function metadataPostTreatment(ClassMetadata $metadata)
    {
        if (!$metadata->methodMetadata) {
            $metadata = null;
        }

        return $metadata;
    }

    // TODO: Is it feasible to reverse-engineer the notation for service controllers?
    private function getControllerNotation(\ReflectionMethod $method)
    {
        $signature = $method->class.'::'.$method->name;

        // check if class is a controller
        $matched = preg_match(
            '#\\\\Controller\\\\([^\\\\]+)Controller::(.+)Action$#',
            $signature,
            $match
        );

        if (!$matched) {
            return null;
        }

        foreach ($this->bundles as $name => $namespace) {
            if (0 !== strpos($method->class, $namespace)) {
                continue;
            }

            // controller notation (AcmeBundle:Foo:foo)
            return $name.':'.$match[1].':'.$match[2];
        }

        return null;
    }

    private function assertParamExistsForMethod(array $params, $name, $method)
    {
        if (!isset($params[$name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The parameter "%s" does not exist for method "%s".',
                    $name,
                    $method
                )
            );
        }
    }
}
