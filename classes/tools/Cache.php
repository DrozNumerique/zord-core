<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class Cache {
    
    protected $root = null;
    
    protected function getPool($type) {
        return new FilesystemCachePool(new Filesystem(new Local($this->root ?? '/tmp/zordCache')), $type);
    }
    
    public static function instance($folder = null) {
        return Zord::getInstance('Cache', $folder ?? Zord::liveFolder('cache'));
    }
    
    public function __construct($root) {
        $this->root = $root;
    }
    
    public function getRoot() {
        return $this->root;
    }
    
    public function setItem($type, $key, $value) {
        $item = $this->getPool($type)->getItem($key)->set($value);
        $this->getPool($type)->save($item);
    }
    
    public function getItem($type, $key) {
        return $this->getPool($type)->getItem($key)->get();
    }
    
    public function hasItem($type, $key) {
        return $this->getPool($type)->hasItem($key);
    }
    
    public function deleteItem($type, $key) {
        $this->getPool($type)->deleteItem($key);
    }
    
    public function clear($type) {
        $this->getPool($type)->clear();
    }
    
    public function keys($type, $pattern = '*') {
        return array_map(
            function ($file) {
                return basename($file);
            },
            glob($this->root.DS.$type.DS.$pattern)
        );
    }
}

?>