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
        $entity = (new UserHasSessionEntity())->retrieveOne($session);
        if ($entity !== false) {
            (new UserHasSessionEntity())->update($session, ['last' => date('Y-m-d H:i:s')]);
        }
        return $entity;
    }
}

?>
