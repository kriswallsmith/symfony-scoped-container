<?php

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Scope\ScopeInterface;

/**
 * A scoped container is a composition of scopes.
 */
interface ScopedContainerInterface extends ContainerInterface
{
    /**
     * Registers a scope to the current container.
     *
     * @param string         $scopeName The scope name
     * @param ScopeInterface $scope     The scope
     */
    function registerScope($scopeName, ScopeInterface $scope)

    /**
     * Enters a scope.
     *
     * @param string $scopeName The scope name
     */
    function enterScope($scopeName);

    /**
     * Leaves a scope.
     *
     * @param string $scopeName The scope name
     */
    function leaveScope($scopeName);
}
