========
Overview
========

This bundle allows you to secure method invocations on your service layer with
annotations.

Generally, you can secure all public, or protected methods which are non-static,
and non-final. Private methods cannot be secured this way.

Annotations can also be declared on abstract methods, parent classes, or 
interfaces.

How does it work?
-----------------
The bundle will first collect all available security metadata for your services
from annotations. The metadata will then be used to build proxy classes which 
have the requested security checks built-in. These proxy classes will replace 
your original service classes. All of that is done automatically for you, you
don't need to manually clear any cache if you make changes to the metadata.


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
Checkout a copy of the code::

    git submodule add https://github.com/schmittjoh/SecurityExtraBundle.git src/JMS/SecurityExtraBundle
    
Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\SecurityExtraBundle\SecurityExtraBundle(),
        // ...
    );

Configuration
-------------

Below, you find the default configuration::

    # app/config/config.yml
    jms_security_extra:
        # If you set-up your controllers as services, you must set this to false;
        # otherwise your security checks will be performed twice.
        secure_controllers: true
        
        # Whether you want to secure all services (true), or only secure specific
        # services (false); see also below 
        secure_all_services: false


By default, security checks are not enabled for any service. You can turn on
security for your services either by securing all services as shown above, or
only for specific services by adding a tag to these services::

    <service id="foo" class="Bar">
        <tag name="security.secure_service"/>
    </service>

If you enable security for all services, be aware that the first page load will
be very slow depending on how many services you have defined.


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
