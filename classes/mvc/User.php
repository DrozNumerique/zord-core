<?php

class User {
    
    public static $ZORD_SESSION = '__ZORD_SESSION__';
    
    public $login;
    public $name;
    public $email;
    public $ips;
    public $session = null;
    public $roles = [];
    
    public function __construct($login = null, $session = null) {
        if ($login) {
            $roles = (new UserEntity())->retrieve($login);
            if ($roles) {
                $this->login = $roles->get('login');
                $this->name = $roles->get('name');
                $this->email = $roles->get('email');
                $this->ips = $roles->get('ips');
                $this->session = $session;
                if ($session != null) {
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
        }
    }
    
    public static function crypt($data) {
        return hash('sha256', SALT.$data);
    }
    
    public static function find($checkIP = true) {
        $result = null;
        $session = null;
        foreach ([$_SESSION, $_COOKIE, $_POST, $_GET] as $var) {
            if (isset($var[self::$ZORD_SESSION]) && is_string($var[self::$ZORD_SESSION])) {
                $session = $var[self::$ZORD_SESSION];
                break;
            }
        }
        if (isset($session)) {
            $result = UserHasSessionEntity::find($session);
        }
        if (!$result) {
            $session = null;
            if ($checkIP) {
                $IP = $_SERVER['REMOTE_ADDR'];
                if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $result = UserHasAddressEntity::find($IP);
                }
            }
        }
        $class = Zord::getClassName('User');
        if ($result) {
            return new $class($result->user, $session);
        }
        return new $class();
    }
    
    public static function authenticate($login, $password) {
        $result = (new UserEntity())->retrieve([
            'where' => [
                'raw' => '(login = ? AND password = ?)',
                'parameters' => [$login, self::crypt($password)]
            ]
        ]);
        if ($result) {
            Zord::log($result);
            $class = Zord::getClassName('User');
            $user = new $class($result->login, self::crypt($login.microtime()));
            (new UserHasSessionEntity())->create([
                'user' => $user->login,
                'session' => $user->session,
                'last' => date('Y-m-d H:i:s')
            ]);
            return $user;
        } else {
            return false;
        }
    }
    
    public function disconnect() {
        session_destroy();
        (new UserHasSessionEntity())->delete($this->session);
        $this->roles = [];
        $this->session = null;
    }
    
    public function isConnected() {
        return $this->session != null;
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
