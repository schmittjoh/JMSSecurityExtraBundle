<?php

namespace JMS\SecurityExtraBundle\Metadata\Driver;

use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use JMS\SecurityExtraBundle\Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;

/**
 * Common implementations for loading security related metadata.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Loads security-related metadata from configuration for a specific class
     *
     * @param \ReflectionClass $class Class for which to load metadata
     *
     * @return ClassMetatada
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $metadata = new ClassMetadata($class->name);

        $classMetadataConfig = $this->getClassScopeMetadata($class);

        $methods = $class->getMethods(
            \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $methodMetadataConfig = $this->getMethodScopeMetadata($method);

            $configuratedMetadata = array(
                'class' => $classMetadataConfig ? $classMetadataConfig : array(),
                'method' => $methodMetadataConfig ? $methodMetadataConfig : array()
            );

            $methodMetadata = $this->fromMetadataConfig($method, $configuratedMetadata);

            if ($methodMetadata) {
                $metadata->addMethodMetadata($methodMetadata);
            }
        }

        return $this->metadataPostTreatment($metadata);
    }

    /**
     * Enables specialized post-treatment for class metadata.
     *
     * This method is mainly a backward compatibility strategy to bypass the
     * violation of liskov substitution principle implied by the conjunction
     * use of MetadataFactory and DriverChain.
     *
     * For more information about this bug, see issue #145 at
     *
     * @param ClassMetadata $metadata
     *
     * @return null|ClassMetadata
     *
     * @see https://github.com/schmittjoh/JMSSecurityExtraBundle/issues/145
     */
    abstract protected function metadataPostTreatment(ClassMetadata $metadata);

    /**
     * Retrieves metadata configuration for specified method.  Implementations
     * should use what configuration source provided by child DriverInterface
     * implementation.
     *
     * @param \ReflectionMethod $method Method for which the configuration will apply
     *
     * @return array An array of metadata configuration which can be arrays, objects, etc.
     */
    abstract protected function getMethodScopeMetadata(\ReflectionMethod $method);

    /**
     * Retrieves metadata configuration for specified method.  Implementations
     * should use what configuration source provided by child DriverInterface
     * implementation.
     *
     * @param \ReflectionClass $class Class for which the configuration will apply
     *
     * @return array An array of metadata configuration which can be arrays, objects, etc.
     */
    abstract protected function getClassScopeMetadata(\ReflectionClass $class);

    /**
     * Converts an array of metadata configuration to a MethodMetadada.
     *
     * @param \ReflectionMethod $method  Related method
     * @param array             $configs Metadata configuration to be converted
     *
     * @return MethodMetadata
     */
    abstract protected function fromMetadataConfig(\ReflectionMethod $method, array $configs);
}
