<?php

class UserEntity extends Entity {
    
    protected function beforeSave($entity, $data) {
        if (isset($data['password'])) {
            $entity->set('password', User::crypt($data['password']));
        }
        return $entity;
    }
}

?>
