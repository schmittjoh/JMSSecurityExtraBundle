<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\AclBundle\AclBundle;
use Symfony\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\FormLoginBundle;
use JMS\SecurityExtraBundle\Tests\Functional\TestBundle\TestBundle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

AnnotationRegistry::registerLoader('class_exists');

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config)
    {
        parent::__construct('test', true);

        $fs = new Filesystem();
        if (!$fs->isAbsolutePath($config)) {
            $config = __DIR__.'/config/'.$config;
        }

        if (!file_exists($config)) {
            throw new \RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;
    }

    public function registerBundles()
    {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new TestBundle(),
            new FormLoginBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \JMS\AopBundle\JMSAopBundle(),
            new \JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new \JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
        );

        if (class_exists(AclBundle::class)) {
            $bundles[] = new AclBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/JMSSecurityExtraBundle/'.Kernel::VERSION.'/'.sha1($this->config).'/cache/';
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/JMSSecurityExtraBundle/'.Kernel::VERSION.'/'.sha1($this->config).'/logs';
    }

    protected function getContainerClass()
    {
        return parent::getContainerClass().sha1($this->config);
    }

    public function serialize()
    {
        return $this->config;
    }

    public function unserialize($str)
    {
        $this->__construct($str);
    }
}
