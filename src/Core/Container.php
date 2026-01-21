<?php
// Path: src/Core/Container.php

namespace UmrahManagement\Core;

use ReflectionClass;
use ReflectionException;
use Exception;

class Container {
    /**
     * Map interface ke class konkret.
     */
    protected $bindings = [];

    /**
     * Instance yang disimpan (Singleton).
     */
    protected $instances = [];

    /**
     * Mendaftarkan binding interface ke class.
     * Contoh: bind(DatabaseInterface::class, WordPressDatabaseAdapter::class)
     */
    public function bind($abstract, $concrete = null) {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Mendaftarkan singleton (instance dibagi pakai).
     */
    public function singleton($abstract, $concrete = null) {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Tandai sebagai singleton
    }

    /**
     * Mengambil instance class (Resolve).
     */
    public function get($abstract) {
        // Cek apakah ini singleton yang sudah dibuat
        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        // Jika konkretnya adalah closure/fungsi
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            // Jika konkretnya adalah nama class, lakukan build (auto-wiring)
            $object = $this->build($concrete);
        }

        // Simpan instance jika ini singleton
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Membuat instance class dan menginject dependency-nya secara otomatis.
     */
    protected function build($concrete) {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // Jika tidak ada constructor, langsung buat instance baru
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Menyelesaikan dependency dari parameter constructor.
     */
    protected function resolveDependencies($dependencies) {
        $results = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if (!$type || $type->isBuiltin()) {
                // Jika tipe primitif (string, int), coba cari nilai default
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new Exception("Unresolvable dependency resolving [$dependency] in class {$dependency->getDeclaringClass()->getName()}");
                }
            } else {
                // Jika tipe class/interface, panggil get() secara rekursif
                $results[] = $this->get($type->getName());
            }
        }

        return $results;
    }
}