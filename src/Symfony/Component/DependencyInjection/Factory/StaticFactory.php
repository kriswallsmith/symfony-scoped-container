<?php

namespace Symfony\Component\DependencyInjection\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add methods and service ids to a subclass to create a static factory.
 */
class StaticFactory implements FactoryInterface
{
    static protected $serviceIds = array();

    /** {@inheritDoc} */
    public function create($id, ContainerInterface $container)
    {
        $method = 'get'.strtr($id, array('_' => '', '.' => '_')).'Service';
        if (method_exists($this, $method)) {
            return call_user_func(array($this, $method), $container);
        }
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        return in_array($id, static::$serviceIds);
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return static::$serviceIds;
    }
}

