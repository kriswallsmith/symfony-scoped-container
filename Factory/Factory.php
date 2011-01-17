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
    private $callables = array();

    /** {@inheritDoc} */
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
