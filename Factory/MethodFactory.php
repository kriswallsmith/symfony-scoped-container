<?php

namespace Symfony\Component\DependencyInjection\Factory;

/**
 * An abstract class that looks for method names that match a service id.
 */
abstract class MethodFactory implements FactoryInterface
{
    /** {@inheritDoc} */
    public function has($id)
    {
        return method_exists($this, $this->convertToMethodName($id));
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        if (method_exists($this, $method = $this->convertToMethodName($id))) {
            return $this->$method();
        }

        // invalid behavior
    }

    /**
     * Converts a service id to a method name.
     *
     * @param string $id A service id
     *
     * @return string A method name
     */
    private function convertToMethodName($id)
    {
        return 'get'.strtr($id, array('_' => '', '.' => '_')).'Service';
    }
}
