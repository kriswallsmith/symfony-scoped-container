<?php

namespace Symfony\Component\DependencyInjection\Scope;

/**
 * The container scope always returns the same instance of a service.
 */
class ContainerScope extends Scope
{
    /**
     * @var array Service instances indexed by id
     */
    private $services = array();

    /** {@inheritDoc} */
    public function has($id)
    {
        return isset($this->services[$id]) || parent::has($id);
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        return $this->services[$id] = parent::get($id);
    }

    /** {@inheritDoc} */
    public function set($id, $service)
    {
        $this->services[$id] = $service;
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return array_unique(array_merge(array_keys($this->services), parent::getServiceIds()));
    }
}
