<?php

namespace Symfony\Component\DependencyInjection\Scope;

/**
 * Nests many container scopes in one upon enter and leave.
 */
class NestingContainerScope extends ContainerScope
{
    /**
     * @var array A FIFO queue of nested scopes
     */
    private $scopes = array();

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
