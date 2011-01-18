<?php

namespace Symfony\Component\DependencyInjection\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A factory is something that creates services.
 */
interface FactoryInterface
{
    /**
     * Registers a callable to the factory.
     *
     * The callable should accept a {@link ContainerInterface} as its only
     * argument. The container should be used to retrieve other service the
     * current one relies on.
     *
     *     $factory->register('db', function(ContainerInterface $container)
     *     {
     *         $db = new Connection();
     *         $db->setLogger($container->get('logger'));
     *         return $db;
     *     });
     *
     * @param string $id       The service id
     * @param mixed  $callable A PHP callable that will create the service
     */
    function register($id, $callable);

    /**
     * Gets an instance of a service.
     *
     * @param string             $id        The service id
     * @param ContainerInterface $container A container for fetching dependencies
     *
     * @return object A new object instance
     */
    function create($id, ContainerInterface $container);

    /**
     * Checks whether the current factory knows how to create the supplied service.
     *
     * @param string $id The service id
     *
     * @return Boolean Whether the service is known to the current factory
     */
    function has($id);

    /**
     * Returns an array of services the current factory knows how to create.
     *
     * @return array An array of service ids
     */
    function getServiceIds();
}
