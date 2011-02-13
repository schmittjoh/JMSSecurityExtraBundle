<?php

namespace JMS\SecurityExtraBundle\Mapping;

/**
 * This class contains metadata for the entire service
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceMetadata
{
    protected $classes;
    protected $methods;
    
    public function __construct()
    {
        $this->classes = array();
    }
    
    public function addMetadata(ClassMetadata $metadata)
    {
        $this->classes[$metadata->getReflection()->getName()] = $metadata;
    }
    
    public function getClasses()
    {
        return $this->classes;
    }
    
    public function getClassMetadata($class)
    {
        if (!isset($this->classes[$class])) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not belong to this service.', $class));
        }
        
        return $this->classes[$class];
    }
    
    public function addMethod($name, MethodMetadata $metadata)
    {
        $this->methods[$name] = $metadata;
    }
    
    public function getMethods()
    {
        return $this->methods;
    }
    
    public function isProxyRequired()
    {
        foreach ($this->classes as $class) {
            if (true === $class->hasMethods()) {
                return true;
            }
        }
        
        return false;
    }
}