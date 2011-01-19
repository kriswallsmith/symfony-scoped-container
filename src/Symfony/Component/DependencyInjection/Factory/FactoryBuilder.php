<?php

namespace Symfony\Component\DependencyInjection\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * The factory builder creates services from definition objects.
 */
class FactoryBuilder implements FactoryInterface
{
    protected $parameterBag;
    protected $definitions;

    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        $this->parameterBag = $parameterBag;
        $this->definitions = array();
    }

    /** {@inheritDoc} */
    public function create($id, ContainerInterface $container)
    {
        $id = strtolower($id);

        if (!isset($this->definitions[$id])) {
            return;
        }

        $definition = $this->definitions[$id];

        if (null !== $definition->getFile()) {
            require_once $this->parameterBag->resolveValue($definition->getFile());
        }

        $arguments = $this->resolveServices($this->parameterBag->resolveValue($definition->getArguments()), $container);

        if (null !== $definition->getFactoryMethod()) {
            if (null !== $definition->getFactoryService()) {
                $factory = $container->get($this->parameterBag->resolveValue($definition->getFactoryService()));
            } else {
                $factory = $this->parameterBag->resolveValue($definition->getClass());
            }

            $service = call_user_func_array(array($factory, $definition->getFactoryMethod()), $arguments);
        } else {
            $r = new \ReflectionClass($this->parameterBag->resolveValue($definition->getClass()));

            $service = null === $r->getConstructor() ? $r->newInstance() : $r->newInstanceArgs($arguments);
        }

        foreach ($this->getInterfaceInjectors($service) as $injector) {
            $injector->processDefinition($definition, $service);
        }

        foreach ($definition->getMethodCalls() as $call) {
            $services = $this->getServiceConditionals($call[1]);

            $ok = true;
            foreach ($services as $s) {
                if (!$container->has($s)) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) {
                call_user_func_array(array($service, $call[0]), $this->resolveServices($this->parameterBag->resolveValue($call[1]), $container));
            }
        }

        if ($callable = $definition->getConfigurator()) {
            if (is_array($callable) && is_object($callable[0]) && $callable[0] instanceof Reference) {
                $callable[0] = $container->get((string) $callable[0]);
            } elseif (is_array($callable)) {
                $callable[0] = $this->parameterBag->resolveValue($callable[0]);
            }

            if (!is_callable($callable)) {
                throw new \InvalidArgumentException(sprintf('The configure callable for class "%s" is not a callable.', get_class($service)));
            }

            call_user_func($callable, $service);
        }

        return $service;
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        $id = strtolower($id);

        return isset($this->definitions[$id]);
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return array_keys($this->definitions);
    }

    /**
     * Registers a service definition.
     *
     * This methods allows for simple registration of service definition
     * with a fluid interface.
     *
     * @param  string $id    The service identifier
     * @param  string $class The service class
     *
     * @return Definition A Definition instance
     */
    public function register($id, $class = null)
    {
        $id = strtolower($id);

        return $this->setDefinition($id, new Definition($class));
    }

    /**
     * Adds the service definitions.
     *
     * @param Definition[] $definitions An array of service definitions
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
     * @param array $definitions An array of service definitions
     */
    public function setDefinitions(array $definitions)
    {
        $this->definitions = array();
        $this->addDefinitions($definitions);
    }

    /**
     * Gets all service definitions.
     *
     * @return array An array of Definition instances
     */
    public function getDefinitions()
    {
        return $this->definitions;
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
        $id = strtolower($id);

        return $this->definitions[$id] = $definition;
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

        return array_key_exists($id, $this->definitions);
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

        if (!$this->hasDefinition($id)) {
            throw new \InvalidArgumentException(sprintf('The service definition "%s" does not exist.', $id));
        }

        return $this->definitions[$id];
    }

    /**
     * Replaces service references by the real service instance.
     *
     * @param  mixed $value A value
     *
     * @return mixed The same value with all service references replaced by the real service instances
     */
    protected function resolveServices($value, ContainerInterface $container)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->resolveServices($v, $container);
            }
        } elseif (is_object($value) && $value instanceof Reference) {
            $value = $container->get((string) $value, $value->getInvalidBehavior());
        }

        return $value;
    }

    protected function getServiceConditionals($value)
    {
        $services = array();

        if (is_array($value)) {
            foreach ($value as $v) {
                $services = array_unique(array_merge($services, $this->getServiceConditionals($v)));
            }
        } elseif (is_object($value) && $value instanceof Reference && $value->getInvalidBehavior() === ContainerInterface::IGNORE_ON_INVALID_REFERENCE) {
            $services[] = (string) $value;
        }

        return $services;
    }
}
