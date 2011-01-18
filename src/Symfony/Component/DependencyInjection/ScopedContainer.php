<?php

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Scope\ScopeInterface;

class ScopedContainer implements ScopedContainerInterface
{
    /**
     * @var array An array of {@link ScopeInterface} instances indexed by name
     */
    private $scopes = array();

    /**
     * @var array A map of scope names to their assigned level
     */
    private $levels = array();

    /**
     * @var array A map of service ids to scope names
     */
    private $serviceMap = array();

    /**
     * @var string The name of the current scope
     */
    private $currentScope;

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

        $this->buildServiceMap();
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
        return isset($this->serviceMap[$id]);
    }

    /** {@inheritDoc} */
    public function get($id)
    {
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
        }
    }

    /** {@inheritDoc} */
    public function set($id, $service, $scopeName = null)
    {
        if (null === $scopeName) {
            // default to the first scope
            $scopeName = current($this->levels);
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
     */
    private function buildServiceMap()
    {
        $serviceMap = array();
        foreach (array_keys($this->levels) as $scopeName) {
            $serviceIds = $this->scopes[$scopeName]->getServiceIds();
            $serviceMap += array_combine(
                $serviceIds,
                array_fill(0, count($serviceIds), $scopeName)
            );
        }

        $this->serviceMap = $serviceMap;
    }
}