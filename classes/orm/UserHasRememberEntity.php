<?php

class UserHasRememberEntity extends Entity {
    
    public static function deleteExpired() {
        (new UserHasRememberEntity())->delete([
            'many' => true,
            'where' => [
                'raw' => 'expiry < NOW()'
            ]
        ]);
    }
    
    public static function find($remember) {
        if (strpos($remember, ':') > 0) {
            list($selector, $validator) = explode(':', $remember);
            $remember = (new UserHasRememberEntity())->retrieve($selector);
            if ($remember !== false && password_verify($validator, $remember->validator)) {
                return $remember;
            }
        }
        return false;
    }

}

?>
