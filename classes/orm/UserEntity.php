<?php

class UserEntity extends Entity {
    
    public function create($data) {
        $entity = parent::create($data);
        self::dependencies($entity, $data);
        return $entity;
    }
    
    public function update($criteria, $data) {
        $update = parent::update($criteria, $data);
        $entities = (new UserEntity())->retrieve($criteria);
        if (!is_iterable($entities)) {
            $entities = [$entities];
        }
        foreach ($entities as $entity) {
            self::dependencies($entity, $data);
        }
        return $update;
    }
    
    private static function dependencies($user, $data) {
        $_user = User::get($user->login);
        foreach ($data as $name => $value) {
            $_user->$name = $value;
        }
        $_user->saveProfile();
        if (!empty($data['ipv4'])) {
            (new UserHasIPV4Entity())->delete(['many' => true, 'user' => $user->login]);
            foreach (explode(',', $user->ipv4) as $IP) {
                $chunk = Zord::chunkIP($IP);
                foreach (Zord::explodeIP($chunk['ip']) as $ip) {
                    $other = UserHasIPEntity::find($ip);
                    if ($chunk['include'] && $other !== false) {
                        throw new Exception($user->login.' collides with '.$other->user.' for IP range '.$ip.'/'.$chunk['mask']);
                    } else {
                        (new UserHasIPV4Entity())->create([
                            'user'    => $user->login,
                            'ip'      => $ip,
                            'mask'    => $chunk['mask'],
                            'include' => $chunk['include'] ? 1 : 0
                        ]);
                    }
                }
            }
        }
    }
    
    protected function beforeSave($entity, $data) {
        if (isset($data['password']) && !($data['password.crypted'] ?? false)) {
            $entity->set('password', User::crypt($data['password']));
        }
        return parent::beforeSave($entity, $data);
    }
}

?>
