<?php

function __autoload($class)
{
    if (0 === strpos($class, 'Symfony\\Component\\DependencyInjection\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
}
