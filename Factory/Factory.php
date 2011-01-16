<?php

namespace Symfony\Component\DependencyInjection\Factory;

class Factory implements FactoryInterface
{
    protected $callables = array();

    /**
     * Registers a callable to the current factory.
     *
     * @param string $id       The service id
     * @param mixed  $callable The factory callable
     */
    public function register($id, $callable)
    {
        $this->callables[$id] = $callable;
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        return isset($this->callables[$id]);
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        if (isset($this->callables[$id])) {
            return call_user_func($this->callables[$id]);
        }

        // invalid behavior
    }
}
