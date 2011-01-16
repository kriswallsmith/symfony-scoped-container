<?php

namespace Symfony\Component\DependencyInjection\Factory;

/**
 * A factory is something that knows how to create services.
 */
interface FactoryInterface
{
    /**
     * Checks if the factory knows how to create a service.
     *
     * @param string $id The service id
     *
     * @return Boolean Whether the factory knows how to create the service
     */
    function has($id);

    /**
     * Creates a new service.
     *
     * @param string $id The service id
     *
     * @return object A new object instance
     */
    function get($id);
}
