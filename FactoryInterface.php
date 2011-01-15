<?php

namespace Symfony\Component\DependencyInjection;

/**
 * A factory is something that knows how to create services.
 */
interface FactoryInterface
{
    function has($id);
    function get($id);
}
