<?php

class Account extends Module {
    
    public $disconnecting = false;
    
    protected function form($action = 'connect', $models = []) {
        $switch = [$action => []];
        if ($action == 'connect') {
            $switch['connect']['before'] = ['reset'];
            $switch['reset']['after'] = ['connect'];
            if (ACCOUNT_AUTO_CREATE) {
                $switch['create']['after'] = ['connect'];
                $switch['connect']['after'] = ['create'];
            }
        } else if ($action == 'password') {
            $models['token'] = $this->params['token'] ?? null;
        }
        $models['switch'] = $switch;
        return ($this->params['xhr'] ?? false) ? $this->view('/portal/page/account', $models) : $this->page('account', $models);
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
                } else if (ACCOUNT_EMAIL_AS_LOGIN) {
                    $user = (new UserEntity())->retrieve($data['email']);
                    if ($user !== false) {
                        $checked = $this->locale->messages->already_email;
                    }
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
            case 'name': {
                if (strlen($this->params['name']) < NAME_MIN_LENGTH || strlen($this->params['name']) > NAME_MAX_LENGTH) {
                    $checked = $this->locale->messages->wrong_name;
                }
                if ($checked !== true) {
                    $spammers = Zord::getConfig('spammers') ?? [];
                    if (!in_array($_SERVER['REMOTE_ADDR'], $spammers)) {
                        $spammers[] = $_SERVER['REMOTE_ADDR'];
                        Zord::saveConfig('spammers', $spammers);
                    }
                }
                break;
            }
        }
        return $checked;
    }
    
    protected function check($data, $scope) {
        $checked = true;
        $properties = Zord::value('account', ['properties',$scope]);
        if ($scope == 'create' && ACCOUNT_EMAIL_AS_LOGIN) {
            $data['login'] = $data['email'];
        }
        foreach ($properties as $property) {
            if (empty($data[$property])) {
                $checked = 'error='.Zord::resolve($this->locale->messages->missing, ['property' => $property], $this->locale);
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
                if ($scope == 'password' && empty($this->user->password)) {
                    $models['password'] = null;
                    $models['confirm'] = null;
                }
            } else if (!empty($data)) {
                $data['password.crypted'] = true;
                $data['reset'] = null;
                (new UserEntity())->update($this->user->login, $data);
                $models['message'] = $this->message('success', $this->locale->messages->$scope->updated);
            } else {
                $models['message'] = $this->message('warning', $this->locale->messages->$scope->unchanged);
            }
        }
        return $this->form($scope, $models);
    }
    
    protected function fullCheck($reset) {
        return $reset.' @ '.$_SERVER['REMOTE_ADDR'];
    }
    
    protected function _password($login) {
        $this->bind($login);
        return $this->user('password');
    }
    
    public function connect() {
        $login    = Zord::trim($this->params['login']    ?? null);
        $password = Zord::trim($this->params['password'] ?? null);
        $success  = Zord::trim($this->params['success']  ?? null);
        $failure  = Zord::trim($this->params['failure']  ?? null);
        $message  = Zord::trim($this->params['message']  ?? null);
        $models = [
            'success' => $success,
            'failure' => $failure,
            'message' => $message,
            'login'   => $login
        ];
        if (!empty($login) && !empty($password)) {
            $user = User::authenticate($login, $password);
            if ($user) {
                $this->controler->setUser($user);
                return $this->redirect($success ?? $this->baseURL, true);
            } else {
                $models['message'] = $this->message('error', $this->locale->messages->auth_failed);
                if (!isset($login)) {
                    unset($models['login']);
                }
                if (!isset($success)) {
                    unset($models['success']);
                }
                if (isset($failure)) {
                    return $this->redirect($failure.(empty(parse_url($failure, PHP_URL_QUERY)) ? '?' : '&').http_build_query($models));
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
                    if ($user !== false) {
                        $check = $user->reset;
                        if (CHECK_IP_WHEN_RESET_PASSWORD) {
                            $reset = $this->fullCheck($reset);
                        } else if (is_string($check)) {
                            $check = substr($check, 0, strlen($reset));
                        }
                        if ($reset === $check) {
                            return $this->_password($login);
                        } else {
                            return $this->error(403);
                        }
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
        return $this->error(400);
    }
    
    public function profile() {
        return $this->user('profile');
    }
    
    public function create() {
        $success = Zord::trim($this->params['success'] ?? null);
        $failure = Zord::trim($this->params['failure'] ?? null);
        if ($this->user->isConnected() || in_array($_SERVER['REMOTE_ADDR'], Zord::getConfig('spammers') ?? [])) {
            return $this->redirect($this->baseURL.'/home');
        }
        list($models, ) = $this->userdata();
        $data = $this->check($models, 'create');
        if (is_string($data)) {
            $models['message'] = $this->message('error', $data);
            if (isset($failure)) {
                return $this->redirect($failure.(empty(parse_url($failure, PHP_URL_QUERY)) ? '?' : '&').http_build_query($models));
            }
        } else {
            $result = $this->notifyReset((new UserEntity())->create($data));
            $models['message'] = $result['error'] ?? $this->message('success', $this->locale->messages->account_created);
            if (isset($result['error']) && isset($failure)) {
                return $this->redirect($failure.(empty(parse_url($failure, PHP_URL_QUERY)) ? '?' : '&').http_build_query($models));
            } else if (isset($failure)) {
                return $this->redirect($success.(empty(parse_url($success, PHP_URL_QUERY)) ? '?' : '&').http_build_query($models));
            }
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
                $models['message'] = $result['error'] ?? $this->message('success', $this->locale->messages->mail_sent);
            } else {
                $models['message'] = $this->message('error', $this->locale->messages->unknown_user);
            }
        }
        return $this->form('reset', $models);
    }
    
    public function disconnect() {
        $this->user->disconnect();
        $this->disconnecting = true;
        $result = $this->params['redirect'] ?? $this->baseURL;
        if (($this->params['xhr'] ?? false)) {
            $this->response = 'DATA';
            return $result;
        }
        return $this->redirect($result, true);
    }
    
    public function notifyProfile($user) {
        if ($user === false) {
            return ['error' => $this->message('error', $this->locale->messages->unknown_user)];
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
            'text'       => $this->locale->mail->notify_profile->text.$user->login."\n".$this->locale->mail->noreply,
            'content'    => '/mail/account/notify',
            'models'     => [
                'login' => $user->login
            ],
            'styles'     => Zord::value('mail', ['styles','account']) ?? null
        ]);
        $result = [
            'account' => htmlspecialchars($user->name.' <'.$user->email.'>')
        ];
        if ($send !== true) {
            $result['error'] = $this->message('error', $this->locale->messages->mail_error).'|'.$this->message('error', $send);
        }
        return $result;
    }
    
    public function notifyReset($user) {
        $now = date('Y-m-d H:i:s');
        (new UserEntity())->update($user->login, ['reset' => $this->fullCheck($now)]);
        $data = Zord::json_encode(['login' => $user->login, 'reset' => $now]);
        $crypted = Zord::encrypt($data, Zord::realpath(OPENSSL_PUBLIC_KEY));
        if ($crypted !== false) {
            $url = $this->baseURL.'/password?token='.base64_encode($crypted);
            $send = PASSWORD_RESET_SEND_MAIL ? $this->sendMail([
                'category'   => 'account'.DS.$user->login,
                'principal'  => ['email' => $user->email, 'name' => $user->name],
                'recipients' => [
                    'bcc' => [
                        WEBMASTER_MAIL_ADDRESS => WEBMASTER_MAIL_NAME
                    ]
                ],
                'subject'    => $this->locale->mail->reset_password->subject.' ('.$user->login.')',
                'text'       => $this->locale->mail->reset_password->copy_paste."\n".$url."\n".$this->locale->mail->noreply,
                'content'    => '/mail/account/reset',
                'models'     => [
                    'url'   => $url,
                    'login' => $user->login
                ],
                'styles'     => Zord::value('mail', ['styles','account']) ?? null
            ]) : false;
            $result = [
                'activation' => $url,
                'account'    => htmlspecialchars($user->name.' <'.$user->email.'>')
            ];
            if ($send !== true) {
                $result['error'] = $this->message('error', $this->locale->messages->mail_error).'|'.$this->message('error', $send);
            }
            return $result;
        }
        return ['error' => $this->message('error', $this->locale->messages->encryption_error)];
    }
}
