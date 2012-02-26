Overview
--------

This bundle enhances the Symfony2 Security Component by adding several new features.

Features:

- powerful expression-based authorization language
- method security authorization
- authorization configuration via annotations
- secure random number generator

Installation
------------
Add the following to your ``deps`` file::

    [JMSSecurityExtraBundle]
        git=https://github.com/schmittjoh/JMSSecurityExtraBundle.git
        target=/bundles/JMS/SecurityExtraBundle
        
    ; Dependencies:
    ;--------------
    [metadata]
        git=https://github.com/schmittjoh/metadata.git
        version=1.1.0 ; <- make sure to get 1.1, not 1.0
    
    ; see https://github.com/schmittjoh/JMSAopBundle/blob/master/Resources/doc/index.rst    
    [JMSAopBundle]
        git=https://github.com/schmittjoh/JMSAopBundle.git
        target=/bundles/JMS/AopBundle
    
    [cg-library]
        git=https://github.com/schmittjoh/cg-library.git
        
    ; This dependency is optional (you need it if you are using non-service controllers):
    ; see https://github.com/schmittjoh/JMSDiExtraBundle/blob/master/Resources/doc/index.rst
    [JMSDiExtraBundle]
        git=https://github.com/schmittjoh/JMSDiExtraBundle.git
        target=/bundles/JMS/DiExtraBundle

Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\AopBundle\JMSAopBundle(),
        new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
        new JMS\DiExtraBundle\JMSDiExtraBundle($this),
        // ...
    );

Make sure that you also register the namespaces with the autoloader::

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'JMS'              => __DIR__.'/../vendor/bundles',
        'Metadata'         => __DIR__.'/../vendor/metadata/src',
        'CG'               => __DIR__.'/../vendor/cg-library/src',
        // ...
    ));

Configuration
-------------

Below, you find the default configuration::

    # app/config/config.yml
    jms_security_extra:
        # Whether you want to secure all services (true), or only secure specific
        # services (false); see also below 
        secure_all_services: false
        
        # Enabling this setting will add an additional special attribute "IS_IDDQD".
        # Anybody with this attribute will effectively bypass all security checks.
        enable_iddqd_attribute: false        
        
        # Enables expression language
        expressions: false

        # Allows you to disable some, or all built-in voters
        voters:
            disable_authenticated: false
            disable_role:          false
            disable_acl:           false
            
        # Allows you to specify access control rules for specific methods, such
        # as controller actions
        method_access_control: { }

        util:
            secure_random:
                connection: # the doctrine connection name
                table_name: seed_table
                seed_provider: # service id of your own seed provider implementation


Expression-based Authorization Language
---------------------------------------

.. toctree::
    :maxdepth: 2
    
    expressions

Method Security Authorization
-----------------------------
Generally, you can secure all public, or protected methods which are non-static,
and non-final. Private methods cannot be secured. You can also add metadata for
abstract methods, or interfaces which will then be applied to their concrete 
implementations automatically.

Access Control via DI configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
You can specify access control **expressions** in the DI configuration::

    # config.yml
    jms_security_extra:
        method_access_control:
            ':loginAction$': 'isAnonymous()'
            'AcmeFooBundle:.*:deleteAction': 'hasRole("ROLE_ADMIN")'
            '^MyNamespace\MyService::foo$': 'hasPermission(#user, "VIEW")' 

The pattern is a case-sensitive regular expression which is matched against two notations.
The first match is being used.

First, your pattern is matched against the notation for non-service controllers. 
This obviously is only done if your class is actually a controller, e.g. 
``AcmeFooBundle:Add:new`` for a controller named ``AddController`` and a method 
named ``newAction`` in a sub-namespace ``Controller`` in a bundle named ``AcmeFooBundle``. 

Last, your pattern is matched against the concatenation of the class name, and
the method name that is being called, e.g. ``My\Fully\Qualified\ClassName::myMethodName``.

**Note:** If you would like to secure non-service controllers, the 
``JMSDiExtraBundle`` must be installed.

Access Control via Annotations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
If you like to secure a service with annotations, you need to enable annotation
configuration for this service::

    <service id="foo" class="Bar">
        <tag name="security.secure_service"/>
    </service>

In case, you like to configure all services via annotations, you can also set
``secure_all_services`` to true. Then, you do not need to add a tag for each 
service.

Secure Random Number Generator
------------------------------
In almost all applications, you need to generate random numbers that cannot be
guessed by a possible attacker. Unfortunately, PHP does not provide capabilities
to do this consistently on all platforms. 

This bundle ships with several seed provider implementations, and will choose
the best provider possible depending on your PHP setup.

You can enable the "security.secure_random" service with the following config::

    jms_security_extra:
        util:
            secure_random: ~

Also make sure to run ``php app/console doctrine:schema:update``, or create an
equivalent migration to import the seed table.


Annotations
-----------
@PreAuthorize
~~~~~~~~~~~~~
This annotation lets you define an expression (see the expression language
paragraph) which is executed prior to invoking a method::

    <?php
    
    use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
    
    class MyService
    {
        /** @PreAuthorize("hasRole('A') or (hasRole('B') and hasRole('C'))") */
        public function secureMethod()
        {
            // ...
        }
    }

@Secure
~~~~~~~
This annotation lets you define who is allowed to invoke a method::

    <?php
    
    use JMS\SecurityExtraBundle\Annotation\Secure;
    
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
    
    use JMS\SecurityExtraBundle\Annotation\SecureParam;
    
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
    
    use JMS\SecurityExtraBundle\Annotation\SecureReturn;
    
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
    
    use JMS\SecurityExtraBundle\Annotation\Secure;
    use JMS\SecurityExtraBundle\Annotation\RunAs;
    
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
