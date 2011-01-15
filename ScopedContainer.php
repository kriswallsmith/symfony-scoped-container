<?php

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Scope\ScopeInterface;

class ScopedContainer implements ScopedContainerInterface
{
    /**
     * @var array Scopes indexed by name
     */
    private $scopes = array();

    /**
     * @var array A map of scope names to levels
     */
    private $levels = array();

    /**
     * Registers a scope to the container.
     *
     * @param string         $name  The scope name
     * @param ScopeInterface $scope The scope
     * @param integer        $level The scope level
     */
    public function registerScope($name, ScopeInterface $scope, $level = 0)
    {
        $this->scopes[$name] = $scope;
        $this->levels[$name] = $level;
        array_multisort($this->levels, SORT_ASC, $this->scopes);
    }

    /**
     * Enters a scope.
     *
     * @param string $name The scope name
     */
    public function enterScope($name)
    {
        if (!isset($this->scopes[$name])) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" scope.', $name));
        }

        $this->scopes[$name]->enter();
    }

    /**
     * Leaves a scope.
     *
     * @param string $name The scope name
     */
    public function leaveScope($name)
    {
        if (!isset($this->scopes[$name])) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" scope.', $name));
        }

        $this->scopes[$name]->leave();
    }

    /**
     * Checks for a service by id.
     */
    public function has($id)
    {
        foreach ($this->scopes as $name => $scope) {
            if ($scope->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves a service by name.
     */
    public function get($id)
    {
        foreach ($this->scopes as $name => $scope) {
            if ($scope->has($id)) {
                return $scope->get($id);
            }
        }
    }

    /**
     * Sets a service by name.
     */
    public function set($id, $service, $scope = null)
    {
        if (null === $scope) {
            reset($this->scopes);
            $scope = key($this->scopes);
        }

        if (!isset($this->scopes[$scope])) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" scope.', $scope));
        }

        $this->scopes[$scope]->set($id, $service);
    }
}
