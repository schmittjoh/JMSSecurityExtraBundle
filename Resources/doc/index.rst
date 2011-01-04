========
Overview
========

This bundle allows you to secure method invocations on your service layer with
annotations (additional drivers like xml, yaml, php, etc. might be added in the
future).

Generally, you can secure all public, or protected methods which are non-static,
and non-final. Private methods cannot be secured this way.

Annotations can also be declared on parent classes, or interfaces. There is a 
restriction however:

If you override a method which has security metadata, right now you always need
to copy over the annotations to your overridden method. In addition, you also
need to add @SatisfiesParentSecurityPolicy, otherwise there will be an exception
when the container is built.

How does it work?
-----------------
The bundle will first collect all available security metadata for your service
(right now only from annotations). The metadata will then be used to build proxy
classes which have the requested security checks built-in. These proxy classes
will replace your original service classes.

Performance
-----------
While there will be virtually no performance difference in your production 
environment, the performance in the development environment significantly
depends on your configuration (see the configuration section).

Generally, you will find that when you change the files of a secure service
the first page load after changing the file will increase. This is because
the cache for this service will need to be rebuilt, and a proxy class possibly
needs to be generated. Subsequent page loads will be very fast.


Installation
------------
SecurityExtraBundle
~~~~~~~~~~~~~~~~~~~
Checkout a copy of the code::

    git submodule add https://github.com/schmittjoh/SecurityExtraBundle.git src/Bundle/JMS/SecurityExtraBundle
    
Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Bundle\JMS\SecurityExtraBundle\SecurityExtraBundle(),
        // ...
    );

Dependencies
~~~~~~~~~~~~
This bundle requires the pdepend library which is used for some code analysis.

You can simply add it as a submodule::

    git submodule add https://github.com/manuelpichler/pdepend.git src/vendor/pdepend
    
You also need to add the following to your autoload.php::

    require_once __DIR__.'/vendor/pdepend/PHP/Depend/Autoload.php';
    $loader = new PHP_Depend_Autoload();
    $loader->register();
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.__DIR__.'/vendor/pdepend');


Configuration
-------------

At the minimum, you need to place the following in your application config, 
e.g. in config.yml::

    security_extra.config: ~
    
This configuration will enable security for all your services. Thus, the first
page load will be very slow (20 seconds upward) depending on how many services
you have. 

You can reduce the overhead by only enabling security for certain services::

    security_extra.config:
        services: [my_secure_service_id, another_secure_service_id]
        
This way only these services will need to be analyzed, and monitored for
changes which will greatly improve the performance in your development
environment.


Annotations
-----------

@Secure
~~~~~~~
This annotation lets you define who is allowed to invoke a method::

    <?php
    
    class MyService
    {
        /**
         * @Secure(roles="ROLE_USER, ROLE_FOO, ROLE_ADMIN")
         */
        public function secureMethod() 
        {
            // ...
        }
    }

@SecureParam
~~~~~~~~~~~~
This annotation lets you define restrictions for parameters which are passed to
the method. This is only useful if the parameters are domain objects::

    <?php
    
    class MyService
    {
        /**
         * @SecureParam(name="comment", permissions="EDIT, DELETE")
         * @SecureParam(name="post", permissions="OWNER")
         */
        public function secureMethod($comment, $post)
        {
            // ...
        }
    }

@SecureReturn
~~~~~~~~~~~~~
This annotation lets you define restrictions for the value which is returned by
the method. This is also only useful if the returned value is a domain object::

    <?php
    
    class MyService
    {
        /**
         * @SecureReturn(permissions="VIEW")
         */
        public function secureMethod()
        {
            // ...
            
            return $domainObject;
        }
    }
    
@RunAs
~~~~~~
This annotation lets you specifiy roles which are added only for the duration 
of the method invocation. These roles will not be taken into consideration 
for before, or after invocation access decisions. 

This is typically used to implement a two-tier service layer where you have 
public and private services, and private services are only to be invoked 
through a specific public service::

    <?php
    
    class MyPrivateService
    {
        /**
         * @Secure(roles="ROLE_PRIVATE_SERVICE")
         */
        public function aMethodOnlyToBeInvokedThroughASpecificChannel()
        {
            // ...
        }
    }
    
    class MyPublicService
    {
        protected $myPrivateService;
    
        /**
         * @Secure(roles="ROLE_USER")
         * @RunAs(roles="ROLE_PRIVATE_SERVICE")
         */
        public function canBeInvokedFromOtherServices()
        {
            return $this->myPrivateService->aMethodOnlyToBeInvokedThroughASpecificChannel();
        }
    }

@SatisfiesParentSecurityPolicy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
This must be defined on a method that overrides a method which has security metadata.
It is there to ensure that you are aware the security of the overridden method cannot
be enforced anymore, and that you must copy over all annotations if you want to keep
them.
