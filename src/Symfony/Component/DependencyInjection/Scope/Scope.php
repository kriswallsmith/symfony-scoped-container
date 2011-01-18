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
    protected $factory;

    /**
     * @var ContainerInterface A container for fetching dependencies
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param FactoryInterface $factory A factory for the current scope's services
     */
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
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if ($instance = $this->factory->create($id, $this->container ?: $this)) {
            return $instance;
        } elseif (ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE == $invalidBehavior) {
            throw new \InvalidArgumentException(sprintf('The service "%s" does not exist.', $id));
        }
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
