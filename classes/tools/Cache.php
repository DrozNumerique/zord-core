<?php
use \Cache\Adapter\Filesystem\FilesystemCachePool;
use \Cache\Prefixed\PrefixedCachePool;
use \League\Flysystem\Adapter\Local;
use \League\Flysystem\Filesystem;

class Cache {
    
    private static function getPool($type) {
        return new FilesystemCachePool(new Filesystem(new Local(CACHE_ROOT_DIR)), $type);
    }
    
    public static function setItem($type, $key, $value) {
        $item = self::getPool($type)->getItem($key)->set($value);
        self::getPool($type)->save($item);
    }
    
    public static function getItem($type, $key) {
        return self::getPool($type)->getItem($key)->get();
    }
    
    public static function hasItem($type, $key) {
        return self::getPool($type)->hasItem($key);
    }
    
    public static function deleteItem($type, $key) {
        self::getPool($type)->deleteItem($key);
    }

}

?>