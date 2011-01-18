<?php

namespace Symfony\Component\DependencyInjection\Scope;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Factory\FactoryInterface;

/**
 * The base scope simply wraps an object factory.
 */
class Scope implements ScopeInterface
{
    /**
     * @var FactoryInterface A factory for creating new instances
     */
    private $factory;

    /**
     * @var ContainerInterface A container for fetching dependencies
     */
    private $container;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /** {@inheritDoc} */
    public function enter()
    {
    }

    /** {@inheritDoc} */
    public function leave()
    {
    }

    /** {@inheritDoc} */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        return $this->factory->has($id);
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        return $this->factory->create($id, $this->container ?: $this);
    }

    /** {@inheritDoc} */
    public function set($id, $service)
    {
    }

    /** {@inheritDoc} */
    public function getServiceIds()
    {
        return $this->factory->getServiceIds();
    }
}
