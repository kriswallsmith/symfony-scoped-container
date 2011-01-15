<?php

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Scope\ScopeInterface;

/**
 * A scoped container is a composition of scopes.
 */
interface ScopedContainerInterface extends ContainerInterface
{
    function registerScope($scopeName, ScopeInterface $scope)
    function enterScope($scopeName);
    function leaveScope($scopeName);
}
