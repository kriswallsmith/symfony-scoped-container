<?php

namespace Symfony\Component\DependencyInjection\Scope;

/**
 * The container scope always returns the same instance of a service.
 */
class ContainerScope extends Scope
{
    protected $services;

    public function has($id)
    {
        return isset($this->services[$id]) || parent::has($id);
    }

    public function get($id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        return $this->services[$id] = parent::get($id);
    }

    public function set($id, $service)
    {
        $this->services[$id] = $service;
    }

    public function remove($id)
    {
        unset($this->services[$id]);
    }
}
