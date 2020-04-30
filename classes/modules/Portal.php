<?php

class Portal extends Module {
    
    public function home() {
        return $this->page('home');
    }
    
    public function token() {
        (new UserHasTokenEntity())->delete([
            'many' => true,
            'where' => [
                'raw' => 'ADDTIME(start, ?) < NOW()',
                'parameters' => TOKEN_INACTIVE_DURATION
            ]
        ]);
        if (isset($this->params['user']) && isset($this->params['key'])) {
            $user = (new UserEntity())->retrieve($this->params['user']);
            $keyfile = Zord::getComponentPath('config'.DS.'keys'.DS.$this->params['user'].DS.$this->params['key'].'.pub');
            if ($user && file_exists($keyfile)) {
                $token = (new UserHasTokenEntity())->retrieve([
                    'many'  => false,
                    'where' => [
                        'user' => $this->params['user'],
                        'key'  => $this->params['key'],
                    ]
                ]);
                if (!$token) {
                    $token = uniqid($user->login, true);
                    $crypted = null;
                    if (openssl_public_encrypt($token, $crypted, openssl_pkey_get_public(file_get_contents($keyfile)))) {
                        (new UserHasTokenEntity())->create([
                            'user'  => $this->params['user'],
                            'key'   => $this->params['key'],
                            'token' => $token,
                            'start' => date('Y-m-d H:i:s')
                        ]);
                        return base64_encode($crypted);
                    } else {
                        return $this->error(500);
                    }
                } else {
                    return $this->error(409);
                }
            } else {
                return $this->error(403);
            }
        } else {
            return $this->error(400);
        }
    }
    
    public function last() {
        $type = isset($this->params['type']) ? $this->params['type'] : 'VIEW';
        $xhr = isset($this->params['xhr']) ? $this->params['xhr'] : false;
        $target = $this->controler->getDefaultTarget();
        if (isset($_SESSION['__ZORD__']['__HISTORY__'][$type]) && count($_SESSION['__ZORD__']['__HISTORY__'][$type]) > 0) {
            $target = end($_SESSION['__ZORD__']['__HISTORY__'][$type]);
            if ($xhr === false) {
                $target['context'] = $this->context;
                $target['indexURL'] = $this->indexURL;
                $target['baseURL'] = $this->baseURL;
                $target['prefix'] = Zord::value('context', [$this->context,'url'])[$this->indexURL]['path'];
            }
        }
        return $this->forward($target, true);
    }
    
    public function config() {
        $config = Zord::getConfig('portal');
        foreach (Zord::getConfig('lang') as $lang => $label) {
            $config['locales'][$lang] = Zord::objectToArray(Zord::getLocale('portal', $lang));
            $config['locales'][$lang]['label'] = $label;
        }
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            $config['baseURL'][$name] = Zord::getContextURL($name);
        }
        return $config;
    }
}

?>
