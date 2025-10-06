<?php

namespace App\Core;

/**
 * Optimized Cache System
 */
class Cache
{
    private $config;
    private $driver;
    private $prefix;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->prefix = $config->get('cache.prefix', 'forum_');
        $this->driver = $config->get('cache.driver', 'file');
        
        $this->initializeDriver();
    }
    
    private function initializeDriver()
    {
        switch ($this->driver) {
            case 'redis':
                $this->initializeRedis();
                break;
            case 'memcached':
                $this->initializeMemcached();
                break;
            default:
                $this->initializeFile();
        }
    }
    
    private function initializeRedis()
    {
        try {
            $redis = new \Redis();
            $redis->connect(
                $this->config->get('cache.redis.host', '127.0.0.1'),
                $this->config->get('cache.redis.port', 6379)
            );
            
            if ($password = $this->config->get('cache.redis.password')) {
                $redis->auth($password);
            }
            
            $this->driver = $redis;
        } catch (\Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
            $this->driver = 'file';
            $this->initializeFile();
        }
    }
    
    private function initializeMemcached()
    {
        try {
            $memcached = new \Memcached();
            $memcached->addServer(
                $this->config->get('cache.memcached.host', '127.0.0.1'),
                $this->config->get('cache.memcached.port', 11211)
            );
            
            $this->driver = $memcached;
        } catch (\Exception $e) {
            error_log('Memcached connection failed: ' . $e->getMessage());
            $this->driver = 'file';
            $this->initializeFile();
        }
    }
    
    private function initializeFile()
    {
        $cacheDir = STORAGE_PATH . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $this->driver = 'file';
    }
    
    public function get($key, $default = null)
    {
        $key = $this->prefix . $key;
        
        switch ($this->driver) {
            case 'redis':
                $value = $this->driver->get($key);
                return $value !== false ? unserialize($value) : $default;
                
            case 'memcached':
                $value = $this->driver->get($key);
                return $value !== false ? $value : $default;
                
            default:
                return $this->getFile($key, $default);
        }
    }
    
    public function put($key, $value, $ttl = 3600)
    {
        $key = $this->prefix . $key;
        
        switch ($this->driver) {
            case 'redis':
                return $this->driver->setex($key, $ttl, serialize($value));
                
            case 'memcached':
                return $this->driver->set($key, $value, $ttl);
                
            default:
                return $this->putFile($key, $value, $ttl);
        }
    }
    
    public function forget($key)
    {
        $key = $this->prefix . $key;
        
        switch ($this->driver) {
            case 'redis':
                return $this->driver->del($key);
                
            case 'memcached':
                return $this->driver->delete($key);
                
            default:
                return $this->forgetFile($key);
        }
    }
    
    public function flush()
    {
        switch ($this->driver) {
            case 'redis':
                $keys = $this->driver->keys($this->prefix . '*');
                if (!empty($keys)) {
                    return $this->driver->del($keys);
                }
                return true;
                
            case 'memcached':
                return $this->driver->flush();
                
            default:
                return $this->flushFile();
        }
    }
    
    public function remember($key, $ttl, $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
    
    private function getFile($key, $default = null)
    {
        $file = STORAGE_PATH . '/cache/' . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }
    
    private function putFile($key, $value, $ttl)
    {
        $file = STORAGE_PATH . '/cache/' . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    private function forgetFile($key)
    {
        $file = STORAGE_PATH . '/cache/' . md5($key) . '.cache';
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    private function flushFile()
    {
        $cacheDir = STORAGE_PATH . '/cache';
        $files = glob($cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    public function getStats()
    {
        switch ($this->driver) {
            case 'redis':
                return $this->driver->info();
                
            case 'memcached':
                return $this->driver->getStats();
                
            default:
                $cacheDir = STORAGE_PATH . '/cache';
                $files = glob($cacheDir . '/*.cache');
                return [
                    'files' => count($files),
                    'size' => array_sum(array_map('filesize', $files))
                ];
        }
    }
}