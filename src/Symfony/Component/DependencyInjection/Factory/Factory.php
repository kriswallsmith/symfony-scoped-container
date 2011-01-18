<?php

namespace Symfony\Component\DependencyInjection\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A factory can have callables registered to it.
 */
class Factory implements FactoryInterface
{
    /**
     * @var array An array of callables for creating services
     */
    protected $callables = array();

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
    public function register($id, $callable)
    {
        $this->callables[$id] = $callable;
    }

    /** {@inheritDoc} */
    public function create($id, ContainerInterface $container)
    {
        if (isset($this->callables[$id])) {
            return call_user_func($this->callables[$id], $container);
        }
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        return isset($this->callables[$id]);
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return array_keys($this->callables);
    }
}
