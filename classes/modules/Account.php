<?php

class Account extends Module {
    
    public $disconnecting = false;
    
    public static function actions($connected) {
        $actions = [];
        if ($connected) {
            $actions[] = $connected;
        } else {
            $actions[] = 'connect';
            $actions[] = 'reset';
            if (ACCOUNT_AUTO_CREATE) {
                $actions[] = 'create';
            }
        }
        return $actions;
    }
    
    protected function form($action = null, $models = []) {
        $models['action']  = $action ?? 'connect';
        $models['actions'] = self::actions($this->user->isConnected() ? $action : false);
        return $this->page('account', $models);
    }
    
    protected function value($property) {
        $value = ($property !== 'confirm') ? $this->user->$property : $this->user->password;
        $submit = false;
        if (isset($this->params[$property])) {
            $submit = true;
            $value = trim($this->params[$property]);
            if (in_array($property, ['password','confirm']) && $value !== $this->user->password) {
                $value = User::crypt($value);
            }
        }
        return [$value, $submit];
    }
    
    protected function userdata() {
        $data = [];
        $update = false;
        $properties = [];
        foreach (Zord::value('account', 'properties') as $list) {
            foreach ($list as $property) {
                if (!in_array($property, $properties)) {
                    $properties[] = $property;
                }
            }
        }
        foreach ($properties as $property) {
            list($value, $submit) = $this->value($property);
            $update = $update || $submit;
            $data[$property] = $value;
        }
        return [$data, $update];
    }
    
    protected function valid($data, $property) {
        $checked = true;
        switch ($property) {
            case 'login': {
                $user = (new UserEntity())->retrieve($data['login']);
                if ($user !== false) {
                    $checked = $this->locale->messages->already_login;
                }
                break;
            }
            case 'email': {
                $user = (new UserEntity())->retrieve([
                    'where' => ['email' => $data['email']]
                ]);
                if ($user !== false && $user->login !== $this->user->login) {
                    $checked = $this->locale->messages->already_email;
                } else if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
                    $checked = $this->locale->messages->notvalid_email;
                }
                break;
            }
            case 'password': {
                if (strlen($this->params['password']) < PASSWORD_MIN_LENGTH) {
                    $checked = Zord::substitute($this->locale->messages->password_length, ['min' => PASSWORD_MIN_LENGTH]);
                } else if ($data['password'] !== $data['confirm']) {
                    $checked = $this->locale->messages->wrong_confirm;
                }
                break;
            }
        }
        return $checked;
    }
    
    protected function check($data, $scope) {
        $checked = true;
        $properties = Zord::value('account', ['properties',$scope]);
        foreach ($properties as $property) {
            if (empty($data[$property])) {
                $checked = Zord::resolve($this->locale->messages->missing, ['property' => $property], $this->locale);
                break;
            }
            $checked = $this->valid($data, $property);
            if ($checked !== true) {
                break;
            }
        }
        if ($checked === true) {
            $checked = [];
            foreach ($properties as $property) {
                if ($data[$property] !== $this->user->$property) {
                    $checked[$property] = $data[$property];
                }
            }
        }
        return $checked;
    }
    
    protected function user($scope) {
        if (!$this->user->isConnected()) {
            return $this->redirect($this->baseURL.'/connect');
        }
        list($models, $update) = $this->userdata();
        if ($update) {
            $data = $this->check($models, $scope);
            if (is_string($data)) {
                $models['message'] = $data;
            } else if (!empty($data)) {
                $data['password.crypted'] = true;
                $data['reset'] = null;
                (new UserEntity())->update($this->user->login, $data);
                $models['message'] = $this->locale->messages->$scope->updated;
            } else {
                $models['message'] = $this->locale->messages->$scope->unchanged;
            }
        }
        return $this->form($scope, $models);
    }
    
    public function connect($success = null, $failure = null) {
        $login    = trim($this->params['login']    ?? '');
        $password = trim($this->params['password'] ?? null);
        $success = $this->params['success'] ?? null;
        $failure = $this->params['failure'] ?? null;
        $message = $this->params['message'] ?? null;
        $models = [
            'success' => $success,
            'failure' => $failure,
            'message' => $message
        ];
        if (!empty($login) && !empty($password)) {
            $user = User::authenticate($login, $password);
            if ($user) {
                $this->controler->setUser($user);
                return $this->redirect($success ?? $this->baseURL, true);
            } else {
                if (isset($failure)) {
                    return $this->redirect($failure.'?message='.$this->locale->messages->auth_failed);
                } else {
                    $models['message'] = $this->locale->messages->auth_failed;
                }
            }
        }
        return $this->form('connect', $models);
    }
    
    public function password() {
        $token = $this->params['token'] ?? null;
        if (isset($token)) {
            $decrypted = Zord::decrypt(base64_decode(str_replace(' ', '+', $token)), Zord::realpath(OPENSSL_PRIVATE_KEY));
            if ($decrypted !== false) {
                $data = Zord::objectToArray(json_decode($decrypted));
                if (is_array($data) && isset($data['login'])) {
                    $login = $data['login'];
                    $reset = $data['reset'];
                    $user = (new UserEntity())->retrieve($login);
                    if ($user !== false && $reset.' @ '.$_SERVER['REMOTE_ADDR'] === $user->reset) {
                        $this->bind($login);
                    } else {
                        return $this->error(404);
                    }
                } else {
                    return $this->error(400);
                }
            } else {
                return $this->error(500);
            }
        }
        return $this->user('password');
    }
    
    public function profile() {
        return $this->user('profile');
    }
    
    public function create() {
        if ($this->user->isConnected()) {
            return $this->redirect($this->baseURL.'/home');
        }
        list($models, ) = $this->userdata();
        $data = $this->check($models, 'create');
        if (is_string($data)) {
            $models['message'] = $data;
        } else {
            $result = $this->notifyReset((new UserEntity())->create($data));
            $models['message'] = $result['error'] ?? $this->locale->messages->account_created;
        }
        return $this->form('create', $models);
    }
    
    public function reset() {
        if ($this->user->isConnected()) {
            return $this->redirect($this->baseURL.'/home');
        }
        $email = $this->params['email'] ?? null;
        $models = [];
        if (isset($email)) {
            $user = (new UserEntity())->retrieve([
                'where' => ['email' => $email]
            ]);
            if ($user !== false) {
                $result = $this->notifyReset($user);
                $models['message'] = $result['error'] ?? $this->locale->messages->mail_sent;
            } else {
                $models['message'] = $this->locale->messages->unknown_user;
            }
        }
        return $this->form('reset', $models);
    }
    
    public function disconnect() {
        $this->user->disconnect();
        $this->disconnecting = true;
        return $this->redirect($this->baseURL, true);
    }
    
    public function notifyProfile($user) {
        if ($user === false) {
            return ['error' => $this->locale->messages->unknown_user];
        }
        $send = $this->sendMail([
            'category'   => 'account'.DS.$user->login,
            'principal'  => ['email' => $user->email, 'name' => $user->name],
            'recipients' => [
                'bcc' => [
                    WEBMASTER_MAIL_ADDRESS => WEBMASTER_MAIL_NAME
                ]
            ],
            'subject'    => $this->locale->mail->notify_profile->subject,
            'text'       => $this->locale->mail->notify_profile->text.$user->login."\n",
            'content'    => '/mail/account/notify',
            'models'     => [
                'login' => $user->login
            ]
        ]);
        $result = [
            'account' => htmlspecialchars($user->name.' <'.$user->email.'>')
        ];
        if ($send !== true) {
            $result['error'] = $this->locale->messages->mail_error.'|'.$send;
        }
        return $result;
    }
    
    public function notifyReset($user) {
        $now = date('Y-m-d H:i:s');
        (new UserEntity())->update($user->login, ['reset' => $now.' @ '.$_SERVER['REMOTE_ADDR']]);
        $data = Zord::json_encode(['login' => $user->login, 'reset' => $now]);
        $crypted = Zord::encrypt($data, Zord::realpath(OPENSSL_PUBLIC_KEY));
        if ($crypted !== false) {
            $url = $this->baseURL.'/password?token='.base64_encode($crypted);
            $send = $this->sendMail([
                'category'   => 'account'.DS.$user->login,
                'principal'  => ['email' => $user->email, 'name' => $user->name],
                'recipients' => [
                    'bcc' => [
                        WEBMASTER_MAIL_ADDRESS => WEBMASTER_MAIL_NAME
                    ]
                ],
                'subject'    => $this->locale->mail->reset_password->subject,
                'text'       => $this->locale->mail->reset_password->copy_paste."\n".$url,
                'content'    => '/mail/account/reset',
                'models'     => [
                    'url' => $url
                ]
            ]);
            $result = [
                'activation' => $url,
                'account'    => htmlspecialchars($user->name.' <'.$user->email.'>')
            ];
            if ($send !== true) {
                $result['error'] = $this->locale->messages->mail_error.'|'.$send;
            }
            return $result;
        }
        return ['error' => $this->locale->messages->encryption_error];
    }
}
