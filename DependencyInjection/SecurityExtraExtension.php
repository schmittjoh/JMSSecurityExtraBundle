<?php

namespace Bundle\JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SecurityExtraExtension extends Extension
{
    public function configLoad($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('security.access.method_interceptor')) {
            $loader = new XmlFileLoader($container, array(__DIR__.'/../Resources/config/'));
            $loader->load('services.xml');
        }

        if (isset($config['services'])) {
            if (!is_array($config['services'])) {
                throw new \RuntimeException('"services" expects an array of service ids.');
            }

            $this->configureServices($config['services'], $container);
        }
    }

    protected function configureServices(array $services, ContainerBuilder $container)
    {
        $normalized = array();
        foreach ($services as $name => $config) {
            if (is_int($name)) {
                $normalized[$config] = array();
            } else {
                if (null === $config) {
                    $config = array();
                } else if (!is_array($config)) {
                    throw new \RuntimeException(sprintf('Invalid configuration; expected array for service "%s", but got %s.', $name, var_export($config, true)));
                }

                $normalized[$name] = $config;
            }
        }

        $container->setParameter('security.secured_services', $normalized);
    }

    public function getAlias()
    {
        return 'security_extra';
    }

    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/security_extra';
    }
}