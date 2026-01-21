<?php

namespace App\Core;

use ReflectionClass;
use Exception;

class Container
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * Bind interface to concrete class
     * * @param string $abstract
     * @param mixed $concrete
     */
    public function bind($abstract, $concrete = null)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Resolve dependency
     * * @param string $abstract
     * @return mixed
     * @throws Exception
     */
    public function get($abstract)
    {
        // 1. Return existing instance (Singleton pattern)
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Resolve binding
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            
            // If closure, execute it
            if ($concrete instanceof \Closure) {
                $object = $concrete($this);
            } else {
                // If string class name, recursive resolve
                $object = $this->get($concrete);
            }
            
            $this->instances[$abstract] = $object;
            return $object;
        }

        // 3. Auto-wiring (Reflection)
        if (class_exists($abstract)) {
            return $this->resolveViaReflection($abstract);
        }

        throw new Exception("Class {$abstract} not found in Container bindings.");
    }

    /**
     * Resolve class using Reflection API
     */
    protected function resolveViaReflection($abstract)
    {
        $reflector = new ReflectionClass($abstract);

        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$abstract} is not instantiable.");
        }

        // Get constructor
        $constructor = $reflector->getConstructor();

        // If no constructor, simpler
        if (is_null($constructor)) {
            return new $abstract;
        }

        // Get constructor params
        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve dependency recursively
     */
    protected function resolveDependencies($parameters)
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            if (!$type) {
                // If no type hint, we can't guess (maybe use default value)
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve parameter {$parameter->name}");
                }
                continue;
            }

            if (!$type->isBuiltin()) {
                // Recursive call to get()
                $dependencies[] = $this->get($type->getName());
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                     throw new Exception("Cannot resolve builtin parameter {$parameter->name}");
                }
            }
        }

        return $dependencies;
    }
}