<?php

namespace Symfony\Component\DependencyInjection;

/**
 * A container is something that knows how to create and store services.
 */
interface ContainerInterface extends FactoryInterface
{
    function set($id, $service);
    function remove($id);
}
