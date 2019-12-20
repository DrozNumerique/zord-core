<?php

class UserHasSessionEntity extends Entity {

    public static function deleteExpired() {
        (new UserHasSessionEntity())->delete(
            [
                'many' => true,
                'where' => [
                    'raw' => 'ADDTIME(last, ?) < NOW()',
                    'parameters' => SESSION_INACTIVE_DURATION
                ]
            ]
        );
    }
    
    public static function find($session) {
        return (new UserHasSessionEntity())->update(
            [
                'key' => $session
            ],
            [
                'last' => date('Y-m-d H:i:s')
            ]
        );
    }
}

?>
