<?php

namespace Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Factory\FactoryInterface;

/**
 * A container is something that knows how to create and store services.
 */
interface ContainerInterface
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
     * Gets an instance of a service.
     *
     * @param string $id The service id
     *
     * @return object A new object instance
     */
    function get($id);

    /**
     * Sets a service on the current container.
     *
     * @param string $id      The service id
     * @param mixed  $service An instance of the service
     */
    function set($id, $service);

    /**
     * Returns an array of service ids the current container supports.
     *
     * @return array An array of service ids
     */
    function getServiceIds();
}
