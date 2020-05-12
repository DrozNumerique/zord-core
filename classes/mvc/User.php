<?php

class User {
    
    public static $ZORD_SESSION = '__ZORD_SESSION__';
    public static $ZORD_TOKEN   = '__ZORD_TOKEN__';
    public static $ZORD_PROPERTIES = [
        'session' => '__ZORD_SESSION__',
        'token'   => '__ZORD_TOKEN__'
    ];
    
    public $login = null;
    public $name = null;
    public $email = null;
    public $ips = null;
    public $password = null;
    public $session = null;
    public $roles = [];
    
    public function __construct($login = null, $session = null, $date = null) {
        if ($login) {
            $this->login = $login;
            $entity = (new UserEntity())->retrieve($login);
            if ($entity) {
                $this->name     = $entity->name;
                $this->email    = $entity->email;
                $this->ips      = $entity->ips;
                $this->password = $entity->password;
                $this->session  = $session;
                if (isset($session)) {
                    $_SESSION[self::$ZORD_SESSION] = $session;
                } else if (isset($_SESSION[self::$ZORD_SESSION])) {
                    unset($_SESSION[self::$ZORD_SESSION]);
                }
                $roles = (new UserHasRoleEntity())->retrieve([
                    'where' => ['user' => $login],
                    'many' => true                       
                ]);
                if ($roles) {
                    foreach ($roles as $entry) {
                        $context = $entry->get('context');
                        $role = $entry->get('role');
                        $start = new DateTime($entry->get('start'));
                        $end = new DateTime($entry->get('end'));
                        $this->roles[$context][$role]['start'] = $start;
                        $this->roles[$context][$role]['end'] = $end;
                    }
                }
            }
            $entity = false;
            if (!isset($date)) {
                $entity = (new UserHasProfileEntity())->retrieve([
                    'where' => [
                        'raw'        => 'user = ? AND date IN (SELECT MAX(date) FROM '.Zord::value('orm', ['UserHasProfileEntity','table']).' GROUP BY user)',
                        'parameters' => [$this->login]
                    ]
                ]);
            } else {
                $iterator = (new UserHasProfileEntity())->retrieve([
                    'many'  => true,
                    'where' => ['user' => $login],
                    'order' => ['desc' => 'date']
                ])->getIterator();
                $entity = false;
                if ($iterator->count() > 0) {
                    $previous = null;
                    $date = date_create($date);
                    while ($current = $iterator->current()) {
                        if ($date > date_create($current->date) &&
                            (!isset($previous) || $date < date_create($previous->date))) {
                            break;
                        }
                        $previous = $current;
                        $iterator->next();
                    }
                    $entity = $current;
                }
            }
            $profile = [];
            if ($entity !== false) {
                $profile = Zord::objectToArray(json_decode($entity->profile));
            } else {
                $profile = $this->profile();
            }
            foreach (Zord::value('portal', ['user','profile']) as $property) {
                if (isset($profile[$property])) {
                    $this->$property = $profile[$property];
                }
            }
        }
    }
    
    public static function crypt($data) {
        $salted = defined('SALT') ? SALT.$data : $data;
        switch (PASSWORD_ALGO) {
            case 'SHA256': {
                return hash('sha256', $salted);
            }
            case 'MD5': {
                return md5($salted);
            }
            default: {
                return null;
            }
        }
    }
    
    public static function find($checkIP = true) {
        $login = null;
        $session = null;
        $token = null;
        foreach (self::$ZORD_PROPERTIES as $property => $key) {
            foreach ([$_SESSION, $_COOKIE, $_POST, $_GET] as $var) {
                if (isset($var[$key]) && is_string($var[$key])) {
                    $$property = $var[$key];
                    break;
                }
            }
        }
        if (isset($session)) {
            $entity = UserHasSessionEntity::find($session);
            $login = $entity ? $entity->user : null;
        } else if (isset($token)) {
            $decrypted = null;
            if (openssl_private_decrypt(base64_decode(str_replace(' ', '+', $token)), $decrypted, openssl_pkey_get_private(file_get_contents(Zord::realpath(OPENSSL_PRIVATE_KEY))))) {
                $token = (new UserHasTokenEntity())->retrieve($decrypted);
                if ($token) {
                    $login = $token->user;
                    (new UserHasTokenEntity())->delete($decrypted);
                }
            }
        }
        if (!isset($login)) {
            $session = null;
            if ($checkIP) {
                $IP = $_SERVER['REMOTE_ADDR'];
                if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $entity = UserHasAddressEntity::find($IP);
                    $login = $entity ? $entity->user : null;
                }
            }
        }
        return self::get($login, $session);
    }
    
    public static function get($login, $session = null, $date = null) {
        $class = Zord::getClassName('User');
        $user = new $class($login, $session, $date);
        return $user;
    }
    
    public static function retrieve($login, $date) {
        return self::get($login, null, $date);
    }
    
    public static function bind($login) {
        $user = self::get($login, self::crypt($login.microtime()));
        (new UserHasSessionEntity())->create([
            'user' => $user->login,
            'session' => $user->session,
            'last' => date('Y-m-d H:i:s')
        ]);
        return $user;
    }
    
    public static function authenticate($login, $password, $transient = true) {
        $result = (new UserEntity())->retrieve([
            'where' => [
                'raw' => '(login = ? AND password = ?)',
                'parameters' => [$login, self::crypt($password)]
            ]
        ]);
        if ($result) {
            if ($transient) {
                return self::bind($login);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
    
    public function profile() {
        $profile = [];
        foreach (Zord::value('portal', ['user','profile']) as $property) {
            if (isset($this->$property)) {
                $profile[$property] = $this->$property;
            }
        }
        return $profile;
    }
    
    public function saveProfile() {
        (new UserHasProfileEntity())->create([
            'user'    => $this->login,
            'profile' => $this->profile()
        ]);
    }
    
    public function disconnect() {
        session_destroy();
        (new UserHasSessionEntity())->delete($this->session);
        $this->roles = [];
        $this->session = null;
    }
    
    public function isKnown() {
        return $this->login !== null;
    }
    
    public function isConnected() {
        return $this->session !== null;
    }
    
    public function hasRole($role, $context, $wild = true) {
        if (!$role) {
            return true;
        }
        $period = null;
        if (isset($this->roles[$context][$role])) {
            $period = $this->roles[$context][$role];
        } else if ($wild) {
            if (isset($this->roles[$context]['*'])) {
                $period = $this->roles[$context]['*'];
            } else if (isset($this->roles['*'][$role])) {
                $period = $this->roles['*'][$role];
            } else if (isset($this->roles['*']['*'])) {
                $period = $this->roles['*']['*'];
            }
        }
        if ($period) {
            $start = $period['start'];
            $end = $period['end'];
            $now = new DateTime();
            return $start <= $now && $now <= $end;
        }
        return false;
    }
    
    public function getContext($role, $wild = true) {
        $list = [];
        foreach (array_keys($this->roles) as $context) {
            if ($this->hasRole($role, $context, $wild)) {
                $list[] = $context;
            }
        }
        return $list;
    }
    
    public function isManager() {
        return $this->hasRole('admin', '*');
    }
    
    public function explodeIP() {
        $result = array();
        if ($this->ips) {
            $ips = explode(',', $this->ips);
            if ($ips) {
                foreach ($ips as $ip) {
                    if (!empty($ip)) {
                        $result[] = Zord::chunkIP($ip);
                    }
                }
            }
        }
        return $result;
    }
}

?>
