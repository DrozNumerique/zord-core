<?php

class Admin extends Module {
        
    public function index($current = null, $models = null) {
        $tabs = Zord::getConfig('admin');
        $scopes = [
            'global'  => '*',
            'context' => $this->context
        ];
        foreach ($tabs as $name => $tab) {
            if (!$this->user->hasRole('admin', $scopes[$tab['scope'] ?? 'global'])) {
                unset($tabs[$name]);
            }
        }
        if (isset($this->params['tab'])) {
            $current = $this->params['tab'];
            $models = [];
        }
        if (!isset($current) && isset($_SESSION['__ZORD__']['__ADMIN__']['__CURRENT__'])) {
            $current = $_SESSION['__ZORD__']['__ADMIN__']['__CURRENT__'];
        }
        if (!isset($models) && isset($_SESSION['__ZORD__']['__ADMIN__']['__MODELS__'])) {
            $models = $this->updateModels($_SESSION['__ZORD__']['__ADMIN__']['__MODELS__']);
        }
        if (isset($models['current'])) {
            $current = $models['current'];
        }
        if (!isset($current)) {
            reset($tabs);
            $current = key($tabs);
        }
        if (!isset($models)) {
            $models = [];
        }
        $_SESSION['__ZORD__']['__ADMIN__']['__CURRENT__'] = $current;
        $_SESSION['__ZORD__']['__ADMIN__']['__MODELS__'] = $models;
        $tab = $tabs[$current];
        foreach (['styles','scripts'] as $type) {
            if (isset($tab[$type])) {
                foreach ($tab[$type] as $model) {
                    $this->addModel($type, $model);
                }
            }
        }
        $this->prepareIndex($current);
        return $this->page('admin', array_merge($models, [
            'tabs'    => array_keys($tabs),
            'current' => $current,
            'admin'   => $this
        ]));
    }
    
    public function account() {
        $result = [];
        if (isset($this->params['operation']) && 
            isset($this->params['login']) &&
            isset($this->params['name']) && 
            isset($this->params['email'])) {
            $operation = $this->params['operation'];
            $login = $this->params['login'];
            $name = $this->params['name'];
            $email = $this->params['email'];
            $entity = new UserEntity();
            if ($login && $operation) {
                switch ($operation) {
                    case 'create': {
                        $data = [
                            'login'    => $login,
                            'name'     => $name,
                            'email'    => $email
                        ];
                        $result = (new Account($this->controler))->notifyReset($entity->create($data));
                        break;
                    }
                    case 'update': {
                        $entity->update($login, [
                            'name' => $name,
                            'email' => $email
                        ]);
                        break;
                    }
                    case 'delete': {
                        $entity->delete($login, true);
                        break;
                    }
                    case 'profile': {
                        $result = $this->dataProfile($login);
                        break;
                    }
                }
            }
        }
        return $this->index('users', $result);
    }
    
    public function profile() {
        $result = [];
        if (isset($this->params['login']) &&
            isset($this->params['roles']) &&
            isset($this->params['ips'])) {
            $login = $this->params['login'];
            $criteria = [
                'where' => ['user' => $login],
                'many' => true
            ];
            (new UserHasRoleEntity())->delete($criteria);
            (new UserHasAddressEntity())->delete($criteria);
            $roles = Zord::objectToArray(json_decode($this->params['roles']));
            foreach ($roles as $role) {
                (new UserHasRoleEntity())->create($role);
            }
            $ips = Zord::objectToArray(json_decode($this->params['ips']));
            $user_ips = array();
            foreach ($ips as $entry) {
                $entryOK = true;
                foreach (Zord::explodeIP($entry['ip']) as $ip) {
                    $other = UserHasAddressEntity::find($ip);
                    if ($entry['include'] && $other) {
                        $entryOK = false;
                        $result['others'][] = [((new UserEntity())->retrieve($other->user)->name).' ('.$other->user.')', $ip];
                        break;
                    }
                }
                if ($entryOK) {
                    foreach (Zord::explodeIP($entry['ip']) as $ip) {
                        (new UserHasAddressEntity())->create([
                            'user'    => $login,
                            'ip'      => $ip,
                            'mask'    => (!empty($entry['mask']) || $entry['mask'] == 0) ? $entry['mask'] : 32,
                            'include' => $entry['include'] ? 1 : $entry['include']
                        ]);
                    }
                    $user_ips[] = ($entry['include'] ? '' : '~').$entry['ip'].((!empty($entry['mask']) || $entry['mask'] == 0) ? '/'.$entry['mask'] : '');
                }
            }
            (new UserEntity())->update($login, ['ips' => implode(',', $user_ips)]);
            $result = array_merge($result, $this->dataProfile($login));
        }
        return $this->index('users', $result);
    }
    
    public function context() {
        $result = [];
        if (isset($this->params['operation']) &&
            isset($this->params['name']) &&
            isset($this->params['title'])) {
            $operation = $this->params['operation'];
            $name = $this->params['name'];
            $title = $this->params['title'];
            $context = Zord::getConfig('context');
            switch ($operation) {
                case 'create': {
                    if (!isset($context[$name])) {
                        $context[$name]['title'][$this->lang] = $title;
                    } else {
                        $result['message'][] = 'context existant';
                    }
                    break;
                }
                case 'update': {
                    if (isset($context[$name])) {
                        $context[$name]['title'][$this->lang] = $title;
                    } else {
                        $result['message'][] = 'context inexistant';
                    }
                    break;
                }
                case 'delete': {
                    if (isset($context[$name])) {
                        unset($context[$name]);
                    } else {
                        $result['message'][] = 'context inexistant';
                    }
                    break;
                }
                case 'urls': {
                    $result = $this->dataURLs($name);
                    break;
                }
            }
            Zord::saveConfig('context', $context);
        }
        return $this->index('context', $result);
    }
    
    public function urls() {
        $result = [];
        if (isset($this->params['ctx']) &&
            isset($this->params['urls'])) {
            $context = Zord::getConfig('context');
            $name = $this->params['ctx'];
            $urls = Zord::objectToArray(json_decode($this->params['urls']));
            if (count($urls) > 0) {
                $context[$name]['url'] = $urls;
            } else {
                unset($context[$name]['url']);
            }
            Zord::saveConfig('context', $context);
            $result['ctx'] = $name;
            $result['urls'] = $urls;
        }
        return $this->index('context', $result);
    }
    
    public function content() {
        $name    = $this->params['name']    ?? null;
        $content = $this->params['content'] ?? null;
        if (isset($content) && isset($name)) {
            $result = null;
            $date = Zord::content($name, $this->lang, $content);
            if (isset($date)) {
                $result = [
                    'date'    => $date,
                    'message' => Zord::substitute(
                        $this->locale->tab->content->message->saved,
                        ['label' => $this->contentLabel($name), 'date' => $date]
                    )
                ];
            }
            return $result ?? $this->error(500, $this->locale->tab->content->message->unsaved);
        }
        return $this->error(400, $this->locale->tab->content->message->missing);
    }
    
    public function contentList() {
        return Zord::value('portal', 'contents') ?? [];
    }
    
    public function contentLabel($name) {
        return $this->locale->tab->content->label->$name;
    }
    
    protected function prepareIndex($current) {
        if ($current == 'content') {
            $contents = Zord::value('portal', 'contents') ?? [];
            foreach ($contents as $content) {
                foreach (['styles','scripts'] as $type) {
                    $entries = Zord::value('page', ['content',$content,$type]) ?? [];
                    foreach ($entries as $model) {
                        $this->addModel($type, $model);
                    }
                }
            }
        }
    }
    
    protected function updateModels($models) {
        return $models;
    }
    
    protected function dataProfile($login) {
        $result = [];
        $user = User::get($login);
        $result['login'] = $login;
        $result['name'] = $user->name;
        $result['ips'] = $user->explodeIP();
        $result['roles'] = array_merge(Zord::getConfig('role'), ['*']);
        $result['contexts'] = array_merge(array_keys(Zord::getConfig('context')), ['*']);
        return $result;
    }
    
    protected function dataURLs($name) {
        $result = [];
        $result['ctx'] = $name;
        $result['urls'] = Zord::value('context', [$name,'url']);
        return $result;
    }
}

?>