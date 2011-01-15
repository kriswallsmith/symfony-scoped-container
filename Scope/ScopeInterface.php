<?php

namespace Symfony\Component\DependencyInjection\Scope;

/**
 * A scope isolates services and can be entered and left.
 */
interface ScopeInterface extends ContainerInterface
{
    function enter();
    function leave();
}
