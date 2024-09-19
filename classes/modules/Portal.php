<?php

class Portal extends Module {
    
    public function home() {
        return $this->page('home');
    }
    
    protected function contentModels($name) {
        return ['name' => $name];
    }
    
    public function content() {
        $name  = $this->params['name'] ?? null;
        $alone = $this->params['alone'] ?? false;
        if (isset($name)) {
            $models = $this->contentModels($name);
            if ($alone === 'true') {
                foreach (['styles','scripts'] as $type) {
                    $model = Zord::value('skin', $type, 'content');
                    if (isset($model)) {
                        $this->addModel($type, $model);
                    }
                    foreach (Zord::value('page', ['content', $name, $type]) ?? [] as $model) {
                        $this->addModel($type, $model);
                    }
                }
                return $this->view('/content', $models);
            } else {
                return $this->page('content', $models);
            }
        } else {
            return $this->error(404);
        }
    }
    
    public function token() {
        UserHasTokenEntity::deleteExpired();
        if (isset($this->params['user']) && isset($this->params['key'])) {
            $user = (new UserEntity())->retrieve($this->params['user']);
            $keyfile = Zord::getComponentPath('config'.DS.'keys'.DS.$this->params['user'].DS.$this->params['key'].'.pub');
            if ($user !== false && file_exists($keyfile)) {
                $token = (new UserHasTokenEntity())->retrieve([
                    'many'  => false,
                    'where' => [
                        'user' => $this->params['user'],
                        'key'  => $this->params['key'],
                    ]
                ]);
                if ($token === false) {
                    $token = Zord::token($keyfile, $this->params['user'], $this->params['key']);
                    if (!isset($token)) {
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
        $type = $this->params['type'] ?? 'VIEW';
        $xhr = $this->params['xhr'] ?? false;
        $target = $this->controler->getDefaultTarget();
        if (isset($_SESSION['__ZORD__']['__HISTORY__'][$type]) && count($_SESSION['__ZORD__']['__HISTORY__'][$type]) > 0) {
            $target = end($_SESSION['__ZORD__']['__HISTORY__'][$type]);
            if ($xhr === false) {
                $target['context'] = $this->context;
                $target['indexURL'] = $this->indexURL;
                $target['baseURL'] = $this->baseURL;
                $target['lastURL'] = $target['pathURL'];
                $target['prefix'] = Zord::value('context', [$this->context,'url'])[$this->indexURL]['path'];
            }
        }
        return $this->forward($target, true);
    }
    
    public function config() {
        $domain = $this->params['domain'] ?? 'portal';
        if (!$this->user->hasRole('admin', $this->context) && !in_array($domain, Zord::value('portal', 'public') ?? [])) {
            return $this->error(401);
        }
        $property = $this->params['property'] ?? null;
        if ($property) {
            return Zord::value($domain, explode(DS, $property));
        } else {
            return Zord::getConfig($domain);
        }
    }
    
    public function locale() {
        $domain = $this->params['domain'] ?? 'portal';
        $lang = $this->params['lang'] ?? $this->lang;
        $property = $this->params['property'] ?? null;
        $locale = Zord::getLocale($domain, $lang, true);
        if ($property) {
            foreach (explode(DS, $property) as $name) {
                $locale = $locale[$name];
            }
        }
        return $locale;
    }
    
    public function options() {
        $scope = $this->params['scope'] ?? 'portal';
        $key = $this->params['key'] ?? null;
        $options = $this->_options($scope, $key);
        Zord::sort($options);
        $options = array_combine(array_map(function($key) {return 'key:'.$key;}, array_keys($options)), array_values($options));
        return $options;
    }
    
    public function messages() {
        return $this->view('/portal/widget/message', ['message' => $this->params['message'] ?? '']);
    }
    
    protected function _options($scope, $key) {
        $values = [];
        if (in_array($scope, ['portal','context','user'])) {
            $values = Zord::value('portal', ['options',$scope]) ?? [];
            if ($scope == 'context') {
                $values = $values[$this->context] ?? ($values['*'] ?? []);
            }
            if ($scope == 'user') {
                $values = $values[$this->user->login] ?? ($values['*'] ?? []);
            }
            if (!isset($key) && Zord::is_associative($values)) {
                $values = array_keys($values);
            } else if (isset($key)) {
                $values = $values[$key] ?? [];
            }
        }
        return $values;
    }
    
    protected function _hashKey($action) {
        switch ($action) {
            case 'config': {
                $this->params['data_locale'] = false;
                $this->params['data_scope']  = 'portal';
                $this->params['data_type']   = 'config';
                $this->params['data_key']    = $this->params['domain'] ?? null;
                break;
            }
            case 'locale': {
                $this->params['data_locale'] = true;
                $this->params['data_scope']  = 'portal';
                $this->params['data_type']   = 'locale';
                $this->params['data_key']    = (!isset($this->params['domain']) && !isset($this->params['lang']) ? null : ($this->params['domain'] ?? '').(isset($this->params['domain']) && isset($this->params['lang']) ? '.' : '').($this->params['lang'] ?? DEFAULT_LANG) ?? '');
                break;
            }
            case 'options': {
                $this->params['data_locale'] = isset($this->params['_lang']);
                $this->params['data_scope']  = $this->params['scope'] ?? 'portal';
                $this->params['data_type']   = 'options';
                $this->params['data_key']    = $this->params['key'] ?? '_keys';
                break;
            }
        }
    }
}

?>
