<?php
class UserHasAddressEntity extends Entity {
    
    public static function find($ip) {
        $includes = self::_find($ip);
        $excludes = self::_find($ip, false);
        foreach ($includes as $include) {
            foreach ($excludes as $exclude) {
                if ($exclude->user == $include->user) {
                    return null;
                }
            }
            return $include;
        }
        return null;
    }
    
    private static function _find($ip, $include = true) {
        return (new UserHasAddressEntity())->retrieve(
            [
                'where' => [
                    'raw' => 'ip = INET_ATON(?) & (-1 << (32 - mask)) AND include = ?',
                    'parameters' => array($ip, $include)
                ],
                'many' => true
            ]
        );
    }
}
?>
