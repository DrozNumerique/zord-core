<?php

class Admin extends Module {
        
    public function index($current = null, $models = array()) {
        if (!$this->user->hasRole('admin', $this->context)) {
            return $this->redirect($this->baseURL, true);
        }
        $tabs = Zord::value('admin', 'tabs');
        $scopes = [
            'zord'    => '*',
            'context' => $this->context
        ];
        foreach ($tabs as $name => $tab) {
            if (!$this->user->hasRole('admin', $scopes[$tab['scope']])) {
                unset($tabs[$name]);
            }
        }
        if (isset($this->params['tab'])) {
            $current = $this->params['tab'];
        }
        if (!isset($current)) {
            reset($tabs);
            $current = key($tabs);
        }
        $tab = $tabs[$current];
        if (isset($tab['scripts'])) {
            foreach ($tab['scripts'] as $script) {
                if (isset($script['src'])) {
                    if (isset($script['type'])) {
                        $this->addScript($script['src'], isset($script['type']) ? $script['type']: '');
                    } else {
                        $this->addScript($script['src']);
                    }
                } else if (isset($script['template'])) {
                    if (isset($script['type'])) {
                        $this->addTemplateScript($script['template'], isset($script['type']) ? $script['type']: '');
                    } else {
                        $this->addTemplateScript($script['template']);
                    }
                }
            }
        }
        return $this->page('admin', array_merge($models, [
            'tabs'    => array_keys($tabs),
            'current' => $current
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
                        $code = User::crypt($login.microtime());
                        $entity->create([
                            'login' => $login,
                            'activate' => $code,
                            'name' => $name,
                            'email' => $email
                        ]);
                        $result['mail'] = $this->sendActivation($email, $name, $code);
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
        if (isset($this->params['user']) &&
            isset($this->params['roles']) &&
            isset($this->params['ips'])) {
            $login = $this->params['user'];
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
            $result['context'] = $name;
            $result['urls'] = $urls;
        }
        return $this->index('context', $result);
    }
    
    public function switches() {
        return Zord::value('admin', 'switches');
    }
    
    private function dataProfile($login) {
        $result = [];
        $result['user'] = new User($login);
        $result['roles'] = array_merge(Zord::getConfig('role'), ['*']);
        $result['context'] = array_merge(array_keys(Zord::getConfig('context')), ['*']);
        return $result;
    }
    
    private function dataURLs($name) {
        $result = [];
        $result['context'] = $name;
        $result['urls'] = Zord::value('context', [$name,'url']);
        return $result;
    }
}

?>