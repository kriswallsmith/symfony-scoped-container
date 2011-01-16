<?php

namespace Symfony\Component\DependencyInjection\Factory;

/**
 * A factory collection hides multiple factories behind one interface.
 */
class FactoryCollection implements FactoryInterface
{
    protected $factories = array();

    public function __construct($factories = array())
    {
        foreach ($factories as $factory) {
            $this->addFactory($factory);
        }
    }

    public function addFactory(FactoryInterface $factory)
    {
        $this->factories[] = $factory;
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        foreach ($this->factories as $factory) {
            if ($factory->has($id)) {
                return true;
            }
        }

        return false;
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        foreach ($this->factories as $factory) {
            if ($factory->has($id)) {
                return $factory->get($id);
            }
        }

        // invalid behavior
    }
}
