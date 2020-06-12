<?php

class Account extends Module {
    
    protected function form($action = null, $models = []) {
        $models['action']  = $action ?? 'connect';
        $models['actions'] = [];
        if (!$this->user->isConnected()) {
            $models['actions'][] = 'connect';
            $models['actions'][] = 'reset';
            if (ACCOUNT_AUTO_CREATE) {
                $models['actions'][] = 'create';
            }
        } else {
            $models['actions'][] = 'profile';
        }
        return $this->page('account', $models);
    }
    
    protected function userdata() {
        $login    = isset($this->params['login'])    ? trim($this->params['login'])    : $this->user->login;
        $name     = isset($this->params['name'])     ? trim($this->params['name'])     : $this->user->name;
        $email    = isset($this->params['email'])    ? trim($this->params['email'])    : $this->user->email;
        $password = isset($this->params['password']) ? trim($this->params['password']) : $this->user->password;
        $confirm  = isset($this->params['confirm'])  ? trim($this->params['confirm'])  : $this->user->password;
        return [
            'login'    => $login,
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'confirm'  => $confirm
        ];
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
                if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                    $checked = Zord::substitute($this->locale->messages->password_length, ['min' => PASSWORD_MIN_LENGTH]);
                } else if ($data['password'] !== $data['confirm']) {
                    $checked = $this->locale->messages->wrong_confirm;
                }
                break;
            }
        }
        return $checked;
    }
    
    protected function value($data, $property) {
        return ($property === 'password') ? User::crypt($data['password']) : $data[$property];
    }
    
    protected function check($data, $properties = []) {
        $checked = true;
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
                if ($this->value($data, $property) !== $this->user->$property) {
                    $checked[$property] = $data[$property];
                }
            }
        }
        return $checked;
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
                }
            }
        }
        return $this->form('connect', $models);
    }
    
    public function profile() {
        $token = $this->params['token'] ?? null;
        if (isset($token)) {
            $decrypted = null;
            if (openssl_private_decrypt(base64_decode(str_replace(' ', '+', $token)), $decrypted, openssl_pkey_get_private(file_get_contents(Zord::realpath(OPENSSL_PRIVATE_KEY))))) {
                $data = Zord::objectToArray(json_decode($decrypted));
                if (is_array($data) && isset($data['login'])) {
                    $login = $data['login'];
                    $user = (new UserEntity())->retrieve($login);
                    if ($user !== false) {
                        $this->controler->setUser(User::bind($login));
                        $this->user = $this->controler->getUser();
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
        if (!$this->user->isConnected()) {
            return $this->redirect($this->baseURL.'/connect');
        }
        $models = $this->userdata();
        if (!isset($token)) {
            $data = $this->check($models, Zord::value('account', ['properties','profile']));
            if (is_string($data)) {
                $models['message'] = $data;
            } else if (!empty($data)) {
                (new UserEntity())->update($this->user->login, $data);
                $models['message'] = $this->locale->messages->profile_updated;
            } else {
                $models['message'] = $this->locale->messages->profile_unchanged;
            }
        }
        return $this->form('profile', $models);
    }
    
    public function create() {
        if ($this->user->isConnected()) {
            return $this->redirect($this->baseURL);
        }
        $models = $this->userdata();
        $data = $this->check($models, Zord::value('account', ['properties','create']));
        if (is_string($data)) {
            $models['message'] = $data;
        } else {
            $result = $this->notifyReset((new UserEntity())->create($data));
            $models['message'] = $result['error'] ?? $this->locale->messages->account_created;
        }
        return $this->form('create', $models);
    }
    
    public function reset() {
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
        return $this->redirect($this->baseURL, true);
    }
    
    public function notifyReset($user) {
        $now = date('Y-m-d H:i:s');
        (new UserEntity())->update($user->login, ['reset' => $now]);
        $data = Zord::json_encode(['login' => $user->login, 'reset' => $now]);
        $crypted = null;
        if (openssl_public_encrypt($data, $crypted, openssl_pkey_get_public(file_get_contents(Zord::realpath(OPENSSL_PUBLIC_KEY))))) {
            $url = $this->baseURL.'/profile?token='.base64_encode($crypted);
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
                $result['error'] = $this->locale->messages->mail_error.$send;
            }
            return $result;
        }
        return ['error' => $this->locale->messages->encryption_error];
    }
}
