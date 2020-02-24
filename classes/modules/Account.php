<?php

class Account extends Module {
    
    private static $FAKE_PASSWORD = '************'; 
    
    public function profile() {
        $update   = isset($this->params['update'])   ? $this->params['update'] == 'true' : false;
        $token    = isset($this->params['token'])    ? trim($this->params['token'])      : null;
        $name     = isset($this->params['name'])     ? trim($this->params['name'])       : null;
        $password = isset($this->params['password']) ? trim($this->params['password'])   : null;
        $confirm  = isset($this->params['confirm'])  ? trim($this->params['confirm'])    : null;
        $message  = null;
        if ($update) {
            $login = null;
            $activated = false;
            if ($this->user->isConnected()) {
                $login = $this->user->login;
            } else if ($token) {
                $user = (new UserEntity())->retrieve(['where' => ['activate' => $token]]);
                if ($user) {
                    $login = $user->login;
                    $activated = isset($user->password);
                }
            }
            if ($login) {
                if ($password == $confirm) {
                    $data = ['name' => $name, 'activate' => ''];
                    if (!empty($password) && $password !== self::$FAKE_PASSWORD) {
                        $data['password'] = $password;
                        $activated = true;
                    }
                    (new UserEntity())->update(['where' => ['login' => $login]], $data);
                    if (!$this->user->isConnected() && $activated) {
                        $this->controler->setUser(User::bind($login));
                    }
                    $message = $this->locale->messages->profile_updated;
                } else {
                    $message = $this->locale->messages->wrong_confirm;
                }
            } else {
                return $this->error(404);
            }
        } else {
            if ($this->user->isConnected()) {
                $name     = $this->user->name;
                $password = self::$FAKE_PASSWORD;
            } else if ($token) {
                $user = (new UserEntity())->retrieve(['where' => ['activate' => $token]]);
                if ($user) {
                    $name     = $user->name;
                    $password = self::$FAKE_PASSWORD;
                    $token = User::crypt($user->login.microtime());
                    (new UserEntity())->update(
                        ['where' => ['login' => $user->login]],
                        ['activate' => $token]
                    );
                } else {
                    return $this->error(404);
                }
            } else {
                return $this->error(404);
            }
        }
        return $this->page('account', [
            'action'   => 'profile',
            'name'     => $name,
            'password' => $password,
            'message'  => $message,
            'token'    => $token
        ]);
    }
    
    public function connect($lasthref = null) {
        $login    = isset($this->params['login'])    ? trim($this->params['login'])    : '';
        $password = isset($this->params['password']) ? trim($this->params['password']) : null;
        $lasthref = isset($this->params['lasthref']) ? $this->params['lasthref']       : $lasthref;
        $message = null;
        if (!empty($login)) {
            if (!empty($password)) {
                $user = User::authenticate($login, $password);
                if ($user) {
                    $this->controler->setUser($user);
                    return $this->redirect($lasthref ?? $this->baseURL, true);
                } else {
                    $message = $this->locale->messages->auth_failed;
                }
            } else {
                $token = User::crypt($login.microtime());
                $user = (new UserEntity())->update(
                    ['where' => ['email' => $login]],
                    ['activate' => $token]
                );
                if (!$user && ACCOUNT_AUTO_CREATE) {
                    $user = (new UserEntity())->create([
                        'login'    => $login,
                        'email'    => $login,
                        'activate' => $token
                    ]);
                }
                if ($user) {
                    $result = $this->sendActivation($user, $token);
                    if (isset($result['error'])) {
                        $message = $this->locale->messages->mail_error.'|('.$result['error'].')';
                    } else {
                        $message = $this->locale->messages->mail_sent;
                    }
                } else {
                    $message = $this->locale->messages->unknown_user;
                }
            }
        } 
        return $this->page('account', [
            'action'   => 'connect',
            'lasthref' => $lasthref,
            'message'  => $message
        ]);
    }
    
    public function disconnect() {
        $this->user->disconnect();
        return $this->redirect($this->baseURL, true);
    }
    
    public function sendActivation($user, $code) {
        $url = $this->baseURL.'/profile?token='.$code;
        $send = $this->sendMail(
            [$user->email => $user->name],
            $this->locale->mail->activate,
            $this->locale->mail->copy_paste."\n".$url,
            '/mail/activation',
            ['url' => $url]
        );
        $result = [
            'activation' => $url,
            'account'    => htmlspecialchars($user->name.' <'.$user->email.'>')
        ];
        if ($send !== true) {
            $result['error'] = $send;
        }
        return $result;
    }
}
