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
        $this->scopes[$scopeName] = $scope;
        $this->levels[$scopeName] = $level;

        foreach ($scope->getServiceIds() as $id) {
            $this->serviceMap[$id] = $scopeName;
        }

        $scope->setContainer($this);
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
        // defaults to the first scope
        if (null === $scopeName) {
            $scopeName = key($this->scopes);
        } elseif (!isset($this->scopes[$scopeName])) {
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
}
