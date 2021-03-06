<?php

namespace Symfony\Component\DependencyInjection\Scope;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Factory\FactoryInterface;

/**
 * The container scope always returns the same instance of a service.
 */
class ContainerScope extends PrototypeScope
{
    /**
     * @var array Service instances indexed by id
     */
    protected $services;

    public function __construct(FactoryInterface $factory = null)
    {
        $this->services = array();

        parent::__construct($factory);
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        $id = strtolower($id);

        return isset($this->services[$id]) || parent::has($id);
    }

    /** {@inheritDoc} */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $id = strtolower($id);

        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        return $this->services[$id] = parent::get($id, $invalidBehavior);
    }

    /** {@inheritDoc} */
    public function set($id, $service)
    {
        $id = strtolower($id);

        $this->services[$id] = $service;
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return array_unique(array_merge(array_keys($this->services), parent::getServiceIds()));
    }
}
