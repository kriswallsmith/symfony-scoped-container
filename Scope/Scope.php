<?php

namespace Symfony\Component\DependencyInjection\Scope;

use Symfony\Component\DependencyInjection\FactoryInterface;

/**
 * The base scope simply wraps an object factory.
 */
class Scope implements ScopeInterface
{
    protected $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function enter()
    {
    }

    public function leave()
    {
    }

    public function has($id)
    {
        return $this->factory->has($id);
    }

    public function get($id)
    {
        return $this->factory->get($id);
    }

    public function set($id, $service)
    {
    }

    public function remove($id)
    {
    }
}
