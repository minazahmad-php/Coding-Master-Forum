<?php

namespace App\Core;

/**
 * Configuration Manager
 * Handles loading and accessing configuration files
 */
class Config
{
    private $config = [];

    public function __construct()
    {
        $this->loadConfigFiles();
    }

    /**
     * Load all configuration files
     */
    private function loadConfigFiles()
    {
        $configPath = APP_PATH . '/Config';
        
        if (!is_dir($configPath)) {
            return;
        }

        $files = glob($configPath . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    /**
     * Get configuration value
     */
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if configuration key exists
     */
    public function has($key)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * Get all configuration
     */
    public function all()
    {
        return $this->config;
    }
}