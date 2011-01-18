This project adds scope to the Symfony2 dependency injection container as a
first class citizen. Scope controls the lifecycle of container services and
enforces rules on what scopes can rely on what other scopes.

For example, since Symfony2 is built to process multiple requests
independently, a critical part of ESI support, those services that rely on the
request service need the request service for the request currently being
processed, not an earlier request. Otherwise two services that both rely on
the request service may receive different request objects, depending on when
the were first instantiated.

You can create a scoped container in plain PHP:

    <?php

    use Symfony\Component\DependencyInjection as DI;

    class Container extends DI\ScopedContainer
    {
        public function __construct($parameterBag = null)
        {
            $this->registerScope('container', new DI\Scope\ContainerScope(new ContainerFactory()));
            $this->registerScope('prototype', new DI\Scope\PrototypeScope(new PrototypeFactory()));
            $this->registerScope('request',   new DI\Scope\NestingContainerScope(new RequestFactory()), 1);

            parent::__construct($parameterBag);
        }
    }

    // some service classes
    class Connection
    {
        public function __construct(Logger $logger) ...
    }
    class Logger { }
    class Response { }
    class Request { }

    // define a factory for each scope
    // all factories do is create services, nothing else
    class ContainerFactory extends DI\Factory\StaticFactory
    {
        protected function createConnectionService($container)
        {
            return new Connection($container->get('logger'));
        }

        protected function createLoggerService($container)
        {
            return new Logger();
        }
    }

    class PrototypeFactory extends DI\Factory\StaticFactory
    {
        protected function createResponseService($container)
        {
            return new Response();
        }
    }

    class RequestFactory extends DI\Factory\StaticFactory
    {
        protected function createRequestService($container)
        {
            return new Request();
        }
    }

Each of these three scopes (container, prototype and request) use a different
scope class (ContainerScope, PrototypeScope and NestingContainerScope) to
implement different service lifecycle rules.

**ContainerScope**

The container scope will always return the same instance of a service. It will
be created when first called, stored in the scope, and that same instance
returned again for successive calls.

**PrototypeScope**

The prototype scope will always create a new instance of whatever services
in contains.

**NestingContainerScope**

The nesting scope functions the same way as the container scope, but is aware
of being entered and left. Upon being entered, the internal collection of
stored services is reset so new instances will be create when called for. When
left, the internal collection of services will revert to the previous
collection, hence the term nested.

Configuring Scopes
------------------

Scopes can be defined in configuration layer. The following scopes are
configured for you by Symfony:

    <scopes default="container">
        <scope name="container" class="Symfony\Component\DependencyInjection\Scope\ContainerScope">
            <argument><scope-factory /></argument>
        </scope>
        <scope name="prototype" class="Symfony\Component\DependencyInjection\Scope\PrototypeScope">
            <argument><scope-factory /></argument>
        </scope>
        <scope name="request" class="Symfony\Component\DependencyInjection\Scope\NestedContainerScope" level="1">
            <argument><scope-factory /></argument>
        </scope>
    </scopes>

You can then assign a service to a scope using the `scope` attribute:

    <services>
        <service id="logger" class="Symfony\Bundle\ZendBundle\Logger\Logger" />
        <service id="request" class="Symfony\Component\HttpFoundation\Request" scope="request" />
        <service id="response" class="Symfony\Component\HttpFoundation\Response" scope="prototype" />
    </services>

If no scope is explicitly defined, the default scope is used.

Scope Levels
------------

You'll notice a level is defined for request scope in the above configuration.
The other two scopes are assigned the default level of 0. A scope's level
determines what other scopes it has access to when creating dependencies.
Scopes have access to services from any other scope registered with an equal
or lesser value. For example, a service in the request scope will have access
to services in the container scope. The container and prototype scopes are on
the same level and can therefore each rely on services from the other.
