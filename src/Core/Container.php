<?php

namespace UmhMgmt\Core;

/**
 * Class Container
 * * Simple Dependency Injection Container using Singleton pattern.
 * Digunakan untuk menghindari penggunaan 'new ClassName()' secara manual
 * dan hardcoded dependencies.
 * * @package UmhMgmt\Core
 */
class Container {
    /**
     * Menyimpan instance class yang sudah diinisialisasi.
     * @var array
     */
    private static $instances = [];

    /**
     * Mengambil instance class. Jika belum ada, akan dibuatkan instance baru.
     * Jika sudah ada, akan mengembalikan instance yang tersimpan (Singleton).
     *
     * @param string $class Nama class lengkap dengan namespace
     * @return object Instance dari class tersebut
     */
    public static function get($class) {
        if (!isset(self::$instances[$class])) {
            // Inisialisasi class baru jika belum ada di container
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Memaksa set instance tertentu (berguna untuk testing atau konfigurasi khusus).
     *
     * @param string $class
     * @param object $instance
     */
    public static function set($class, $instance) {
        self::$instances[$class] = $instance;
    }
}