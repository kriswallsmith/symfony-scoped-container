<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Factory\FactoryBuilder;
use Symfony\Component\DependencyInjection\InterfaceInjector;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Resource\FileResource;
use Symfony\Component\DependencyInjection\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\Scope\ScopeInterface;

/**
 * ContainerBuilder is a DI container that provides an API to easily describe services.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class ContainerBuilder extends Container implements TaggedContainerInterface
{
    static protected $extensions = array();

    /**
     * @var array An array of factories, indexed by scope name
     */
    protected $factories = array();

    protected $aliases          = array();
    protected $loading          = array();
    protected $resources        = array();
    protected $extensionConfigs = array();
    protected $injectors        = array();
    protected $compiler;

    /**
     * Registers an extension.
     *
     * @param ExtensionInterface $extension An extension instance
     */
    static public function registerExtension(ExtensionInterface $extension)
    {
        static::$extensions[$extension->getAlias()] = static::$extensions[$extension->getNamespace()] = $extension;
    }

    /**
     * Returns an extension by alias or namespace.
     *
     * @param string $name An alias or a namespace
     *
     * @return ExtensionInterface An extension instance
     */
    static public function getExtension($name)
    {
        if (!isset(static::$extensions[$name])) {
            throw new \LogicException(sprintf('Container extension "%s" is not registered', $name));
        }

        return static::$extensions[$name];
    }

    /**
     * Checks if an extension is registered.
     *
     * @param string $name An alias or a namespace
     *
     * @return Boolean
     */
    static public function hasExtension($name)
    {
        return isset(static::$extensions[$name]);
    }

    /**
     * Constructor
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        parent::__construct($parameterBag);

        $this->compiler = new Compiler();
        foreach ($this->compiler->getPassConfig()->getPasses() as $pass) {
            $this->addObjectResource($pass);
        }
    }

    /**
     * Registers a scope to the current container.
     *
     * @param string $scopeName The scope name
     * @param ScopeInterface $scope The scope
     * @param integer $level The level
     */
    public function registerScope($scopeName, ScopeInterface $scope, $level = 0)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Cannot register a scope on a frozen container.');
        }

        parent::registerScope($scopeName, $scope, $level);

        // drop in a factory we can work with
        if (!isset($this->factories[$scopeName])) {
            $this->factories[$scopeName] = new FactoryBuilder($this->parameterBag);
        }
        $scope->setFactory($this->factories[$scopeName]);
    }

    /**
     * Returns an array of scopes.
     *
     * @return array An array of scope names and scopes
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Returns an array of scope levels.
     *
     * @return array An array of scope names and levels
     */
    public function getScopeLevels()
    {
        return $this->levels;
    }

    /**
     * Returns an array of factory builders.
     *
     * @return array An array of scope names and factories
     */
    public function getFactories()
    {
        return $this->factories;
    }

    /**
     * Returns an array of resources loaded to build this configuration.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources()
    {
        return array_unique($this->resources);
    }

    /**
     * Adds a resource for this configuration.
     *
     * @param ResourceInterface $resource A resource instance
     *
     * @return ContainerBuilder The current instance
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Adds the object class hierarchy as resources.
     *
     * @param object $object An object instance
     */
    public function addObjectResource($object)
    {
        $parent = new \ReflectionObject($object);
        do {
            $this->addResource(new FileResource($parent->getFileName()));
        } while ($parent = $parent->getParentClass());
    }

    /**
     * Loads the configuration for an extension.
     *
     * @param string $extension The extension alias or namespace
     * @param string $tag       The extension tag to load (without the namespace - namespace.tag)
     * @param array  $values    An array of values that customizes the extension
     *
     * @return ContainerBuilder The current instance
     */
    public function loadFromExtension($extension, $tag, array $values = array())
    {
        if (true === $this->isFrozen()) {
            throw new \LogicException('Cannot load from an extension on a frozen container.');
        }

        $namespace = $this->getExtension($extension)->getAlias();

        if (!isset($this->extensionConfigs[$namespace.':'.$tag])) {
            $this->extensionConfigs[$namespace.':'.$tag] = array();
        }

        $this->extensionConfigs[$namespace.':'.$tag][] = $this->getParameterBag()->resolveValue($values);

        return $this;
    }

    /**
     * Adds a compiler pass at the end of the current passes
     *
     * @param CompilerPassInterface $pass
     * @param string                $type
     */
    public function addCompilerPass(CompilerPassInterface $pass, $type = PassConfig::TYPE_BEFORE_OPTIMIZATION)
    {
        $this->compiler->addPass($pass, $type);

        $this->addObjectResource($pass);
    }

    /**
     * Returns the compiler pass config which can then be modified
     *
     * @return PassConfig
     */
    public function getCompilerPassConfig()
    {
        return $this->compiler->getPassConfig();
    }

    /**
     * Returns the compiler instance
     *
     * @return Compiler
     */
    public function getCompiler()
    {
        return $this->compiler;
    }

    /**
     * Sets a service.
     *
     * @param string $id      The service identifier
     * @param object $service The service instance
     * @param string $scope   The scope name
     *
     * @throws BadMethodCallException
     */
    public function set($id, $service, $scope = null)
    {
        if ($this->isFrozen()) {
            throw new \BadMethodCallException('Setting service on a frozen container is not allowed');
        }

        unset($this->aliases[strtolower($id)]);

        parent::set($id, $service, $scope);
    }

    /**
     * Returns true if the given service is defined.
     *
     * @param  string  $id      The service identifier
     *
     * @return Boolean true if the service is defined, false otherwise
     */
    public function has($id)
    {
        $id = strtolower($id);

        $this->buildServiceMap();

        return isset($this->aliases[$id]) || parent::has($id);
    }

    /**
     * Gets a service.
     *
     * @param  string $id              The service identifier
     * @param  int    $invalidBehavior The behavior when the service does not exist
     *
     * @return object The associated service
     *
     * @throws \InvalidArgumentException if the service is not defined
     * @throws \LogicException if the service has a circular reference to itself
     *
     * @see Reference
     */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $id = strtolower($id);

        $this->buildServiceMap();

        try {
            return parent::get($id, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE);
        } catch (\InvalidArgumentException $e) {
            if (isset($this->loading[$id])) {
                throw new \LogicException(sprintf('The service "%s" has a circular reference to itself.', $id), 0, $e);
            }

            if (!$this->hasDefinition($id) && isset($this->aliases[$id])) {
                return $this->get($this->aliases[$id]);
            }

            try {
                $definition = $this->getDefinition($id);
            } catch (\InvalidArgumentException $e) {
                if (ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE !== $invalidBehavior) {
                    return null;
                }

                throw $e;
            }

            $this->loading[$id] = true;

            $service = $this->createService($definition, $id);

            unset($this->loading[$id]);

            return $service;
        }
    }

    /**
     * Merges a ContainerBuilder with the current ContainerBuilder configuration.
     *
     * Service definitions overrides the current defined ones.
     *
     * But for parameters, they are overridden by the current ones. It allows
     * the parameters passed to the container constructor to have precedence
     * over the loaded ones.
     *
     * $container = new ContainerBuilder(array('foo' => 'bar'));
     * $loader = new LoaderXXX($container);
     * $loader->load('resource_name');
     * $container->register('foo', new stdClass());
     *
     * In the above example, even if the loaded resource defines a foo
     * parameter, the value will still be 'bar' as defined in the ContainerBuilder
     * constructor.
     */
    public function merge(ContainerBuilder $container)
    {
        if (true === $this->isFrozen()) {
            throw new \LogicException('Cannot merge on a frozen container.');
        }

        $this->addDefinitions($container->getDefinitions());
        $this->addAliases($container->getAliases());
        $this->addInterfaceInjectors($container->getInterfaceInjectors());
        $this->parameterBag->add($container->getParameterBag()->all());

        foreach ($container->getResources() as $resource) {
            $this->addResource($resource);
        }

        foreach ($container->getExtensionConfigs() as $name => $configs) {
            if (isset($this->extensionConfigs[$name])) {
                $this->extensionConfigs[$name] = array_merge($this->extensionConfigs[$name], $configs);
            } else {
                $this->extensionConfigs[$name] = $configs;
            }
        }
    }

    /**
     * Returns the containers for the registered extensions.
     *
     * @return ExtensionInterface[] An array of extension containers
     */
    public function getExtensionConfigs()
    {
        return $this->extensionConfigs;
    }

    /**
     * Sets the extension configs array
     *
     * @param array $config
     * @return void
     */
    public function setExtensionConfigs(array $config)
    {
        $this->extensionConfigs = $config;
    }

    /**
     * Compiles the container.
     *
     * This method passes the container to compiler
     * passes whose job is to manipulate and optimize
     * the container.
     *
     * The main compiler passes roughly do four things:
     *
     *  * The extension configurations are merged;
     *  * Parameter values are resolved;
     *  * The parameter bag is frozen;
     *  * Extension loading is disabled.
     */
    public function compile()
    {
        $this->compiler->compile($this);

        $this->setExtensionConfigs(array());

        parent::compile();
    }

    /**
     * Gets all service ids.
     *
     * @return array An array of all defined service ids
     */
    public function getServiceIds()
    {
        $this->buildServiceMap();

        return array_unique(array_merge(array_keys($this->aliases), parent::getServiceIds()));
    }

    /**
     * Adds the service aliases.
     *
     * @param array $aliases An array of aliases
     */
    public function addAliases(array $aliases)
    {
        foreach ($aliases as $alias => $id) {
            $this->setAlias($alias, $id);
        }
    }

    /**
     * Sets the service aliases.
     *
     * @param array $definitions An array of service definitions
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = array();
        $this->addAliases($aliases);
    }

    /**
     * Sets an alias for an existing service.
     *
     * @param string $alias The alias to create
     * @param mixed  $id    The service to alias
     */
    public function setAlias($alias, $id)
    {
        $alias = strtolower($alias);

        if (is_string($id)) {
            $id = new Alias($id);
        } elseif (!$id instanceof Alias) {
            throw new \InvalidArgumentException('$id must be a string, or an Alias object.');
        }

        $this->removeDefinition($alias);

        $this->aliases[$alias] = $id;
    }

    /**
     * Removes an alias.
     *
     * @param string $alias The alias to remove
     */
    public function removeAlias($alias)
    {
        unset($this->aliases[strtolower($alias)]);
    }

    /**
     * Returns true if an alias exists under the given identifier.
     *
     * @param  string  $id The service identifier
     *
     * @return Boolean true if the alias exists, false otherwise
     */
    public function hasAlias($id)
    {
        return isset($this->aliases[strtolower($id)]);
    }

    /**
     * Gets all defined aliases.
     *
     * @return array An array of aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Gets an alias.
     *
     * @param  string  $id The service identifier
     *
     * @return string The aliased service identifier
     *
     * @throws \InvalidArgumentException if the alias does not exist
     */
    public function getAlias($id)
    {
        $id = strtolower($id);

        if (!$this->hasAlias($id)) {
            throw new \InvalidArgumentException(sprintf('The service alias "%s" does not exist.', $id));
        }

        return $this->aliases[$id];
    }

    /**
     * Adds an InterfaceInjector.
     *
     * @param InterfaceInjector $injector
     */
    public function addInterfaceInjector(InterfaceInjector $injector)
    {
        $class = $injector->getClass();
        if (isset($this->injectors[$class])) {
            return $this->injectors[$class]->merge($injector);
        }

        $this->injectors[$class] = $injector;
    }

    /**
     * Adds multiple InterfaceInjectors.
     *
     * @param array $injectors An array of InterfaceInjectors
     */
    public function addInterfaceInjectors(array $injectors)
    {
        foreach ($injectors as $injector) {
            $this->addInterfaceInjector($injector);
        }
    }

    /**
     * Gets defined InterfaceInjectors.  If a service is provided, only that
     * support the service will be returned.
     *
     * @param string $service If provided, only injectors supporting this service will be returned
     *
     * @return array An array of InterfaceInjectors
     */
    public function getInterfaceInjectors($service = null)
    {
        if (null === $service) {
            return $this->injectors;
        }

        return array_filter($this->injectors, function(InterfaceInjector $injector) use ($service) {
            return $injector->supports($service);
        });
    }

    /**
     * Returns true if an InterfaceInjector is defined for the class.
     *
     * @param string $class The class
     *
     * @return boolean true if at least one InterfaceInjector is defined, false otherwise
     */
    public function hasInterfaceInjectorForClass($class)
    {
        return array_key_exists($class, $this->injectors);
    }

    /**
     * Sets the defined InterfaceInjectors.
     *
     * @param array $injectors An array of InterfaceInjectors indexed by class names
     */
    public function setInterfaceInjectors(array $injectors)
    {
        $this->injectors = $injectors;
    }

    /**
     * Registers a service definition.
     *
     * This methods allows for simple registration of service definition
     * with a fluid interface.
     *
     * @param  string $id    The service identifier
     * @param  string $class The service class
     * @param  string $scope The scope name
     *
     * @return Definition A Definition instance
     */
    public function register($id, $class = null, $scope = null)
    {
        $definition = new Definition($class);

        if (null !== $scope) {
            $definition->setScope($scope);
        }

        return $this->setDefinition(strtolower($id), $definition);
    }

    /**
     * Removes a definition.
     *
     * @param string $id The service identifier
     */
    public function removeDefinition($id)
    {
        $id = strtolower($id);

        unset($this->aliases[$id]);

        foreach ($this->factories as $factory) {
            $factory->removeDefinition($id);
        }
    }

    /**
     * Adds the service definitions.
     *
     * @param array $definitions An array of service ids and definitions
     */
    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $id => $definition) {
            $this->setDefinition($id, $definition);
        }
    }

    /**
     * Sets the service definitions.
     *
     * @param array $definitions An array of service ids and definitions
     */
    public function setDefinitions(array $definitions)
    {
        foreach ($this->factories as $factory) {
            $factory->setDefinitions(array());
        }

        $this->addDefinitions($definitions);
    }

    /**
     * Gets all service definitions.
     *
     * @return array An array of Definition instances
     */
    public function getDefinitions()
    {
        $definitions = array();
        foreach ($this->factories as $factory) {
            $definitions = array_merge($definitions, $factory->getDefinitions());
        }
        return $definitions;
    }

    /**
     * Sets a service definition.
     *
     * @param  string     $id         The service identifier
     * @param  Definition $definition A Definition instance
     *
     * @throws BadMethodCallException
     */
    public function setDefinition($id, Definition $definition)
    {
        if ($this->isFrozen()) {
            throw new \BadMethodCallException('Adding definition to a frozen container is not allowed');
        }

        $id = strtolower($id);

        $this->removeDefinition($id);

        if (!$scopeName = $definition->getScope()) {
            $definition->setScope($scopeName = $this->defaultScope);
        }

        if (!isset($this->factories[$scopeName])) {
            $this->factories[$scopeName] = new FactoryBuilder($this->parameterBag);
        }

        return $this->factories[$scopeName]->setDefinition($id, $definition);
    }

    /**
     * Returns true if a service definition exists under the given identifier.
     *
     * @param  string  $id The service identifier
     *
     * @return Boolean true if the service definition exists, false otherwise
     */
    public function hasDefinition($id)
    {
        $id = strtolower($id);

        foreach ($this->factories as $factory) {
            if ($factory->hasDefinition($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a service definition.
     *
     * @param  string  $id The service identifier
     *
     * @return Definition A Definition instance
     *
     * @throws \InvalidArgumentException if the service definition does not exist
     */
    public function getDefinition($id)
    {
        $id = strtolower($id);

        foreach ($this->factories as $factory) {
            if ($factory->hasDefinition($id)) {
                return $factory->getDefinition($id);
            }
        }

        throw new \InvalidArgumentException(sprintf('The service definition "%s" does not exist.', $id));
    }

    /**
     * Gets a service definition by id or alias.
     *
     * The method "unaliases" recursively to return a Definition instance.
     *
     * @param  string  $id The service identifier or alias
     *
     * @return Definition A Definition instance
     *
     * @throws \InvalidArgumentException if the service definition does not exist
     */
    public function findDefinition($id)
    {
        $id = strtolower($id);

        if ($this->hasAlias($id)) {
            return $this->findDefinition((string) $this->getAlias($id));
        }

        return $this->getDefinition($id);
    }

    /**
     * Returns service ids for a given tag.
     *
     * @param string $name The tag name
     *
     * @return array An array of tags
     */
    public function findTaggedServiceIds($name)
    {
        $tags = array();
        foreach ($this->getDefinitions() as $id => $definition) {
            if ($definition->getTag($name)) {
                $tags[$id] = $definition->getTag($name);
            }
        }

        return $tags;
    }
}
