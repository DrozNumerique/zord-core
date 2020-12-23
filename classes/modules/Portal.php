<?php

class Portal extends Module {
    
    public function home() {
        return $this->page('home');
    }
    
    public function content() {
        $name  = $this->params['name'] ?? null;
        $alone = $this->params['alone'] ?? false;
        if (isset($name)) {
            if ($alone === 'true') {
                foreach (['styles','scripts'] as $type) {
                    $model = Zord::value('skin', $type, 'content');
                    if (isset($model)) {
                        $this->addModel($type, $model);
                    }
                    $models = Zord::value('page', ['content', $name, $type]);
                    if (isset($models)) {
                        foreach ($models as $model) {
                            $this->addModel($type, $model);
                        }
                    }
                }
                return $this->view('/content', ['name' => $name]);
            } else {
                return $this->page('content', ['name' => $name]);
            }
        } else {
            return $this->error(404);
        }
    }
    
    public function token() {
        (new UserHasTokenEntity())->delete([
            'many' => true,
            'where' => [
                'raw' => 'ADDTIME(start, ?) < NOW() AND `key` IS NOT NULL',
                'parameters' => TOKEN_INACTIVE_DURATION
            ]
        ]);
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
        foreach ($config['lang'] as $lang => $label) {
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
