<?php

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\DependencyInjection\Scope\ScopeInterface;

class ScopedContainer implements ScopedContainerInterface
{
    protected $parameterBag;
    protected $loading = array();

    /**
     * @var array An array of {@link ScopeInterface} instances indexed by name
     */
    protected $scopes = array();

    /**
     * @var array A map of scope names to their assigned level
     */
    protected $levels = array();

    /**
     * @var array A map of service ids to scope names
     */
    protected $serviceMap = array();

    /**
     * @var string The name of the current scope
     */
    protected $currentScope;

    /**
     * Constructor.
     *
     * @param ParameterBagInterface $parameterBag A ParameterBagInterface instance
     */
    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        $this->parameterBag = $parameterBag ?: new ParameterBag();
    }

    /**
     * Compiles the container.
     *
     * This method does two things:
     *
     *  * Parameter values are resolved;
     *  * The parameter bag is frozen.
     */
    public function compile()
    {
        $this->parameterBag->resolve();

        $this->parameterBag = new FrozenParameterBag($this->parameterBag->all());
    }

    /**
     * Returns true if the container parameter bag are frozen.
     *
     * @return Boolean true if the container parameter bag are frozen, false otherwise
     */
    public function isFrozen()
    {
        return $this->parameterBag instanceof FrozenParameterBag;
    }

    /**
     * Gets the service container parameter bag.
     *
     * @return ParameterBagInterface A ParameterBagInterface instance
     */
    public function getParameterBag()
    {
        return $this->parameterBag;
    }

    /**
     * Gets a parameter.
     *
     * @param  string $name The parameter name
     *
     * @return mixed  The parameter value
     *
     * @throws  \InvalidArgumentException if the parameter is not defined
     */
    public function getParameter($name)
    {
        return $this->parameterBag->get($name);
    }

    /**
     * Checks if a parameter exists.
     *
     * @param  string $name The parameter name
     *
     * @return boolean The presence of parameter in container
     */
    public function hasParameter($name)
    {
        return $this->parameterBag->has($name);
    }

    /**
     * Sets a parameter.
     *
     * @param string $name       The parameter name
     * @param mixed  $parameters The parameter value
     */
    public function setParameter($name, $value)
    {
        $this->parameterBag->set($name, $value);
    }

    /**
     * Registers a scope to the container.
     *
     * The scope should be "fully baked" when registered since service ids
     * are mapped at this time.
     *
     * @param string         $scopeName The scope name
     * @param ScopeInterface $scope     The scope
     * @param integer        $level     The scope level
     */
    public function registerScope($scopeName, ScopeInterface $scope, $level = 0)
    {
        if (isset($this->scopes[$scopeName])) {
            // DuplicateScopeException
            throw new \LogicException(sprintf('There is already a "%s" scope registered.', $scopeName));
        }

        $scope->setContainer($this);

        $this->scopes[$scopeName] = $scope;
        $this->levels[$scopeName] = $level;
        asort($this->levels);

        $this->serviceMap = $this->buildServiceMap();
    }

    /** {@inheritDoc} */
    public function enterScope($scopeName)
    {
        if (!isset($this->scopes[$scopeName])) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" scope.', $scopeName));
        }

        $this->scopes[$scopeName]->enter();
    }

    /** {@inheritDoc} */
    public function leaveScope($scopeName)
    {
        if (!isset($this->scopes[$scopeName])) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" scope.', $scopeName));
        }

        $this->scopes[$scopeName]->leave();
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        $id = strtolower($id);

        return isset($this->serviceMap[$id]);
    }

    /** {@inheritDoc} */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $id = strtolower($id);

        if (isset($this->serviceMap[$id])) {
            $scopeName = $this->serviceMap[$id];

            if (null === $this->currentScope) {
                $this->currentScope = $scopeName;
            } elseif ($this->levels[$scopeName] > $this->levels[$this->currentScope]) {
                // InaccessibleScopeException
                throw new \LogicException(sprintf('Services in the "%s" scope (i.e. "%s") are not available to services in the "%s" scope.', $scopeName, $id, $this->currentScope));
            }

            // fetch service
            $instance = $this->scopes[$scopeName]->get($id);

            // reset level
            $this->currentScope = null;

            return $instance;
        } elseif (ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE == $invalidBehavior) {
            throw new \InvalidArgumentException(sprintf('The service "%s" does not exist.', $id));
        }
    }

    /** {@inheritDoc} */
    public function set($id, $service, $scopeName = null)
    {
        $id = strtolower($id);

        if (null === $scopeName) {
            // use the mapped scope or default to the first scope
            $scopeName = isset($this->serviceMap[$id]) ? $this->serviceMap[$id] : key($this->levels);
        } elseif (isset($this->serviceMap[$id]) && $this->serviceMap[$id] != $scopeName) {
            // ScopeMismatchException
            throw new \LogicException(sprintf('There is already a "%s" service set on the "%s" scope.', $id, $this->serviceMap[$id]));
        } elseif (!isset($this->scopes[$scopeName])) {
            // InvalidScopeException
            throw new \InvalidArgumentException(sprintf('There is no "%s" scope.', $scopeName));
        }

        $this->scopes[$scopeName]->set($id, $service);
        $this->serviceMap[$id] = $scopeName;
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return array_keys($this->serviceMap);
    }

    /**
     * Resets the service map.
     *
     * Scopes from lower levels will take precedence over duplicate ids from
     * higher level scopes.
     *
     * @return array A map of service ids to scope names
     */
    protected function buildServiceMap()
    {
        $serviceMap = array();
        foreach ($this->levels as $scopeName => $level) {
            foreach ($this->scopes[$scopeName]->getServiceIds() as $id) {
                if (!isset($serviceMap[$id])) {
                    $serviceMap[$id] = $scopeName;
                }
            }
        }

        return $serviceMap;
    }
}
