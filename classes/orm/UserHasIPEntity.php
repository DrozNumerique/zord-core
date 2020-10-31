<?php
abstract class UserHasIPEntity extends Entity {
    
    public static function find($ip) {
        $version = self::version($ip);
        if (isset($version)) {
            $includes = (new $version())->match($ip);
            $excludes = (new $version())->match($ip, false);
            foreach ($includes as $include) {
                foreach ($excludes as $exclude) {
                    if ($exclude->user == $include->user) {
                        return false;
                    }
                }
                return $include;
            }
        }
        return false;
    }
    
    public static function version($ip) {
        $version = null;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $version = 'UserHasIPV4Entity';
        } else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $version = 'UserHasIPV6Entity';
        }
        return $version;
    }
    
    abstract function match($ip, $include = true);
}
?>