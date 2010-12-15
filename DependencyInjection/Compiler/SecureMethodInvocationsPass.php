<?php

namespace Bundle\JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Bundle\JMS\SecurityExtraBundle\Annotation\SecureParam;

use Symfony\Component\DependencyInjection\Resource\FileResource;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\SecurityContext;
use Bundle\JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationReader;
use \ReflectionClass;
use \ReflectionMethod;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class SecureMethodInvocationsPass implements CompilerPassInterface
{
    protected $reader;
    protected $cacheDir;
    
    public function __construct($cacheDir)
    {
        $this->reader = new AnnotationReader();
        $this->reader->setDefaultAnnotationNamespace('Bundle\\JMS\\SecurityExtraBundle\\Annotation\\');

        $finder = new Finder;
        $finder
            ->name('*.php')
            ->in(__DIR__.'/../../Annotation/')
        ;
        foreach ($finder as $annotationFile) {
            require_once $annotationFile->getPathName();
        }

        $this->reader->setAnnotationCreationFunction(function($name, $value) {
            $reflection = new ReflectionClass($name);
            if (!$reflection->implementsInterface('Bundle\\JMS\\SecurityExtraBundle\\Annotation\\AnnotationInterface')) {
                return null;
            }

            $annotation = new $name();
            foreach ($value as $key => $value) {
                $annotation->$key = $value;
            }

            return $annotation;
        });

        $cacheDir .= '/security/';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        if (false === is_writable($cacheDir)) {
            die ('Cannot write to cache folder: '.$cacheDir);
        }
        $this->cacheDir = $cacheDir;
    }
    
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            $this->processDefinition($container, $definition);
        }
    }

    protected function processDefinition(ContainerBuilder $container, Definition $definition)
    {
        if (null === $class = $definition->getClass()) {
            return;
        }

        $reflection = new ReflectionClass($class);
        $methods = array();
        do {
            $methods = array_merge($methods, $reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC));
            $container->addResource(new FileResource($reflection->getFileName()));
        } while (false !== $reflection = $reflection->getParentClass());

        $proxy = '';
        foreach ($methods as $method) {
            $annotations = $this->reader->getMethodAnnotations($method);
            
            if (0 < count($annotations)) {
                if ('' === $proxy) {
                    $proxy = $this->getClassDefinition($definition);
                }
                $proxy .= $this->getMethodDefinition($method);

                $parameters = array();
                foreach ($method->getParameters() as $param) {
                    $parameters[$param->getName()] = true;
                }

                foreach ($annotations as $annotation) {
                    if ($annotation instanceof Secure) {
                        $proxy .= '    if (!$this->jmsSecurityExtraBundle__securityContext'
                                 .'->vote('.var_export(explode(',', $annotation->roles), true).')) {
            throw new Symfony\Component\Security\Exception\AccessDeniedException();
        }

    ';
                    }

                    if ($annotation instanceof SecureParam) {
                        if (!isset($parameters[$annotation->name])) {
                            throw new \InvalidArgumentException('The parameter "'.$annotation->name.'" does not exist.');
                        }

                        $proxy .= '    if (!$this->jmsSecurityExtraBundle__securityContext->vote('.var_export(explode(',', $annotation->permissions), true).', $'.$annotation->name.')) {
            throw new Symfony\Component\Security\Exception\AccessDeniedException();
        }

    ';
                    }
                }

                $proxy .= '    $result = '.$this->getMethodCall($method).';

        return $result;
    }

    ';
            }
        }
        
        if ('' !== $proxy) {
            $proxy = substr($proxy, 0, -5).'}';
        }

        if (strlen($proxy) > 0) {
            $reflection = new ReflectionClass($definition->getClass());
            file_put_contents($this->cacheDir.basename($reflection->getFileName()), $proxy);

            
            $definition->setClass('Bundle\\JMS\\SecurityExtraBundle\\Proxy\\'.substr($definition->getClass(), strrpos($definition->getClass(), '\\') + 1));
            $definition->addMethodCall('jmsSecurityExtraBundle__setSecurityContext', array(new Reference('security.context')));
        }
    }

    protected function getClassDefinition(Definition $definition)
    {
        return sprintf('<?php

namespace Bundle\JMS\SecurityExtraBundle\Proxy;

class %s extends %s
{
    protected $jmsSecurityExtraBundle__securityContext;

    public function jmsSecurityExtraBundle__setSecurityContext(Symfony\Component\Security\SecurityContext $context)
    {
        $this->jmsSecurityExtraBundle__securityContext = $context;
    }

    ', substr($definition->getClass(), strrpos($definition->getClass(), '\\') +1), $definition->getClass());
    }

    protected function getMethodCall(ReflectionMethod $method)
    {
        $def = '';

        if ($method->returnsReference()) {
            $def .= '&';
        }

        $def .= 'parent::'.$method->getName().'(';
        foreach ($method->getParameters() as $param) {
            $def .= '$'.$param->getName().', ';
        }
        
        return substr($def, 0, -2). ')';
    }

    protected function getMethodDefinition(ReflectionMethod $method)
    {
        $def = '';
        if ($method->isProtected()) {
            $def .= 'protected ';
        } else {
            $def .= 'public ';
        }

        if ($method->isStatic()) {
            $def .= 'static ';
        }

        if ($method->returnsReference()) {
            $def .= '&';
        }

        $def .= 'function '.$method->getName().'(';
        foreach ($method->getParameters() as $param) {
            if (null !== $class = $param->getClass()) {
                $def .= $class->getName().' ';
            } else if ($param->isArray()) {
                $def .= 'array ';
            }

            if ($param->isPassedByReference()) {
                $def .= '&';
            }

            $def .= '$'.$param->getName();

            if ($param->isOptional()) {
                $def .= ' = '.var_export($param->getDefaultValue(), true);
            }

            $def .= ', ';
        }
        $def = substr($def, 0, -2).')
    {
    ';

        return $def;
    }
}