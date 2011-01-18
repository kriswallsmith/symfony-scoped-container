<?php

namespace Symfony\Component\DependencyInjection\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add methods and service ids to a subclass to create a static factory.
 */
class StaticFactory implements FactoryInterface
{
    static protected $serviceIds;

    /** {@inheritDoc} */
    public function create($id, ContainerInterface $container)
    {
        $method = 'create'.strtr($id, array('_' => '', '.' => '_')).'Service';
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
        if (null === static::$serviceIds) {
            static::$serviceIds = array();

            $r = new \ReflectionObject($this);
            foreach ($r->getMethods() as $method) {
                if (preg_match('/^create(.+)Service$/', $method->getName(), $match)) {
                    static::$serviceIds[] = static::underscore($match[1]);
                }
            }
        }

        return static::$serviceIds;
    }

    static public function underscore($id)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($id, '_', '.')));
    }
}
