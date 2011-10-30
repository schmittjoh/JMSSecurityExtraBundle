========
Overview
========

This bundle enhances the Symfony2 Security Component by adding several new features.

Features:

  - powerful expression-based authorization language
  - method security authorization
  - authorization configuration via annotations

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
            authenticated: true
            role:          true
            acl:           true


Expression-based Authorization Language
---------------------------------------
The expression language is a very powerful alternative to the simple attributes
of the security voting system. They allow to perform complex access decision
checks, and because they are compiled down to raw PHP, they are much faster than
the built-in voters.

Programmatic Usage
~~~~~~~~~~~~~~~~~~
You can execute expressions programmatically by using the ``isGranted`` method
of the SecurityContext. Some examples::

    use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
    
    $securityContext->isGranted(new Expression('hasRole("A")'));
    $securityContext->isGranted(new Expression('hasRole("A") or (hasRole("B") and hasRole("C")'));
    $securityContext->isGranted(new Expression('hasPermission(object, "VIEW")'), $object);
    $securityContext->isGranted(new Expression('token.getUsername() == "Johannes"');

Annotation-based Usage
~~~~~~~~~~~~~~~~~~~~~~
see @PreAuthorize in the annotation reference

Reference
~~~~~~~~~
+===================================+============================================+
| Expression                        | Description                                |
+===================================+============================================+
| hasRole('ROLE')                   | Checks whether the token has a certain     |
|                                   | role.                                      |
+-----------------------------------+--------------------------------------------+
| hasAnyRole('ROLE1', 'ROLE2', ...) | Checks whether the token has any of the    |
|                                   | given roles.                               |
+-----------------------------------+--------------------------------------------+
| isAnonymous()                     | Checks whether the token is anonymous.     |
+-----------------------------------+--------------------------------------------+
| isRememberMe()                    | Checks whether the token is remember me.   |
+-----------------------------------+--------------------------------------------+
| isFullyAuthenticated()            | Checks whether the token is fully          |
|                                   | authenticated.                             |
+-----------------------------------+--------------------------------------------+
| isAuthenticated()                 | Checks whether the token is not anonymous. |
+-----------------------------------+--------------------------------------------+
| hasPermission(var, 'PERMISSION')  | Checks whether the token has the given     |
|                                   | permission for the given object (ACL).     |
+-----------------------------------+--------------------------------------------+
| token                             | Variable that grants access to the token   |
|                                   | which is currently in the security context.|
+-----------------------------------+--------------------------------------------+
| user                              | Variable that grants access to the user    |
|                                   | which is currently in the security context.|
+-----------------------------------+--------------------------------------------+
| object                            | Variable that grants access to the object  |
|                                   | which was passed to the access decision    |
|                                   | manager.                                   |
+-----------------------------------+--------------------------------------------+
| and / &&                          | Binary "and" operator                      |
+-----------------------------------+--------------------------------------------+
| or / ||                           | Binary "or" operator                       |
+-----------------------------------+--------------------------------------------+
| ==                                | Binary "is equal" operator                 |
+-----------------------------------+--------------------------------------------+


Method Security Authorization
-----------------------------
Generally, you can secure all public, or protected methods which are non-static,
and non-final. Private methods cannot be secured this way.

Annotations can also be declared on abstract methods, parent classes, or 
interfaces.

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
