<?php

class UserEntity extends Entity {
    
    public function create($data) {
        $entity = parent::create($data);
        self::profile($entity, $data);
        return $entity;
    }
    
    public function update($criteria, $data) {
        $update = parent::update($criteria, $data);
        $entities = (new UserEntity())->retrieve($criteria);
        if (!is_iterable($entities)) {
            $entities = [$entities];
        }
        foreach ($entities as $entity) {
            self::profile($entity, $data);
        }
        return $update;
    }
    
    private static function profile($user, $data) {
        $user = User::get($user->login);
        foreach ($data as $name => $value) {
            $user->$name = $value;
        }
        $user->saveProfile();
    }
    
    protected function beforeSave($entity, $data) {
        if (isset($data['password']) && !($data['password.crypted'] ?? false)) {
            $entity->set('password', User::crypt($data['password']));
        }
        return parent::beforeSave($entity, $data);
    }
}

?>
