<?php

namespace SistemTravel\UmrohManagement\Core;

/**
 * Simple Dependency Injection Container
 */
class Container
{
    protected $bindings = [];
    protected $instances = [];

    /**
     * Bind a class or interface to a closure or instance.
     */
    public function bind($abstract, $concrete = null)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind a singleton instance.
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Mark as singleton
    }

    /**
     * Resolve a dependency.
     */
    public function make($abstract)
    {
        // Return existing singleton instance if available
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        // If explicitly bound
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            
            // If it's a closure/factory, execute it
            if ($concrete instanceof \Closure) {
                $object = $concrete($this);
            } else {
                // If it's a class string, instantiate it
                $object = new $concrete($this);
            }
        } else {
            // If not bound, try to instantiate directly
            if (class_exists($abstract)) {
                $object = new $abstract($this);
            } else {
                // Last resort: just return string or null (or throw Exception)
                return null;
            }
        }

        // Save if singleton
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }
    
    // Alias for make
    public function get($id)
    {
        return $this->make($id);
    }
}