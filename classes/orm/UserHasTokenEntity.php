<?php

class UserHasTokenEntity extends Entity {
    
    public static function deleteExpired() {
        (new UserHasRememberEntity())->delete([
            'many' => true,
            'where' => [
                'raw' => 'ADDTIME(start, ?) < NOW() AND `key` IS NOT NULL',
                'parameters' => TOKEN_INACTIVE_DURATION
            ]
        ]);
    }
    
    public static function find($token) {
        $decrypted = Zord::decrypt(base64_decode(str_replace(' ', '+', $token)), Zord::realpath(OPENSSL_PRIVATE_KEY));
        if ($decrypted !== false) {
            $token = (new UserHasTokenEntity())->retrieve($decrypted);
            if ($token) {
                if ($token->key !== null) {
                    (new UserHasTokenEntity())->delete($decrypted);
                }
                return $token;
            }
        }
        return false;
    }
    
}

?>