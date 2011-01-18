<?php

namespace Symfony\Component\DependencyInjection\Scope;

use Symfony\Component\DependencyInjection\Factory\FactoryInterface;

/**
 * Nests many container scopes in one upon enter and leave.
 */
class NestingContainerScope extends ContainerScope
{
    /**
     * @var array A LIFO queue of nested scopes
     */
    protected $scopes;

    public function __construct(FactoryInterface $factory = null)
    {
        $this->scopes = array();

        parent::__construct($factory);
    }

    public function enter()
    {
        // stash the parent scope and start a new one
        $this->scopes[] = $this->services;
        $this->services = array();
    }

    public function leave()
    {
        // restore the parent scope
        $this->services = array_pop($this->scopes);
    }
}
