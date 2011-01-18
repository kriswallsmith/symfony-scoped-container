<?php

namespace Symfony\Component\DependencyInjection\Scope;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A scope manages service lifecycles and can be entered and left.
 */
interface ScopeInterface extends ContainerInterface
{
    /**
     * Enters a new scope.
     */
    function enter();

    /**
     * Leaves the current scope.
     */
    function leave();

    /**
     * Provide a container of services the current scope has access to.
     *
     * @param ContainerInterface|null $container A container
     */
    function setContainer(ContainerInterface $container = null);
}
