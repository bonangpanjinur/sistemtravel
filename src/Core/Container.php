<?php

namespace App\Core;

class Container
{
    protected $bindings = [];
    protected $instances = [];

    public function bind($abstract, $concrete = null)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Mark as singleton
    }

    public function make($abstract, $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    public function get($abstract)
    {
        return $this->resolve($abstract);
    }

    public function has($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    protected function resolve($abstract, $parameters = [])
    {
        if (isset($this->instances[$abstract]) && !is_null($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            // If not bound, try to instantiate directly if class exists
            if (class_exists($abstract)) {
                $reflector = new \ReflectionClass($abstract);
                if (!$reflector->isInstantiable()) {
                    throw new \Exception("Class {$abstract} is not instantiable.");
                }
                $constructor = $reflector->getConstructor();
                if (is_null($constructor)) {
                    return new $abstract;
                }
                // Recursive resolution for dependencies would go here
                // For now, simple instantiation
                return $reflector->newInstance(); 
            }
             throw new \Exception("No binding found for {$abstract}");
        }

        $concrete = $this->bindings[$abstract];
        
        // If the concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } else {
            // If concrete is a class name, resolve it
            $object = $this->resolve($concrete);
        }

        // If it was registered as a singleton (key exists in instances), save the instance
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }
}