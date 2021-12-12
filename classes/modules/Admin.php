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
        return $this->page('admin', array_merge($this->prepareIndex($current, $models), [
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
            $account = Zord::getInstance('Account', $this->controler);
            if ($login && $operation) {
                switch ($operation) {
                    case 'create': {
                        $result = $account->notifyReset($entity->create([
                            'login' => $login,
                            'name'  => $name,
                            'email' => $email
                        ]));
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
                    case 'notify': {
                        $result = $account->notifyProfile($entity->retrieve($login));
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
            isset($this->params['ipv4'])  &&
            isset($this->params['ipv6'])) {
            $login = $this->params['login'];
            $criteria = [
                'where' => ['user' => $login],
                'many' => true
            ];
            (new UserHasRoleEntity())->delete($criteria);
            (new UserHasIPV4Entity())->delete($criteria);
            (new UserHasIPV6Entity())->delete($criteria);
            $roles = Zord::objectToArray(json_decode($this->params['roles']));
            foreach ($roles as $role) {
                (new UserHasRoleEntity())->create($role);
            }
            $ipv4 = Zord::objectToArray(json_decode($this->params['ipv4']));
            $user_ipv4 = array();
            foreach ($ipv4 as $entry) {
                $entryOK = true;
                foreach (Zord::explodeIP($entry['ip']) as $ip) {
                    $other = UserHasIPEntity::find($ip);
                    if ($entry['include'] && $other) {
                        $entryOK = false;
                        $result['others'][] = [((new UserEntity())->retrieve($other->user)->name).' ('.$other->user.')', $ip];
                        break;
                    }
                }
                if ($entryOK) {
                    foreach (Zord::explodeIP($entry['ip']) as $ip) {
                        (new UserHasIPV4Entity())->create([
                            'user'    => $login,
                            'ip'      => $ip,
                            'mask'    => (!empty($entry['mask']) || $entry['mask'] == 0) ? $entry['mask'] : 32,
                            'include' => $entry['include'] ? 1 : $entry['include']
                        ]);
                    }
                    $user_ipv4[] = ($entry['include'] ? '' : '~').$entry['ip'].((!empty($entry['mask']) || $entry['mask'] == 0) ? '/'.$entry['mask'] : '');
                }
            }
            $ipv6 = Zord::objectToArray(json_decode($this->params['ipv6']));
            $user_ipv6 = array();
            foreach ($ipv6 as $entry) {
                $entryOK = true;
                $other = UserHasIPEntity::find($entry['ip']);
                if ($entry['include'] && $other) {
                    $entryOK = false;
                    $result['others'][] = [((new UserEntity())->retrieve($other->user)->name).' ('.$other->user.')', $entry['ip']];
                }
                if ($entryOK) {
                    (new UserHasIPV6Entity())->create([
                        'user'    => $login,
                        'ip'      => $entry['ip'],
                        'mask'    => (!empty($entry['mask']) || $entry['mask'] == 0) ? $entry['mask'] : 32,
                        'include' => $entry['include'] ? 1 : $entry['include']
                    ]);
                    $user_ipv6[] = ($entry['include'] ? '' : '~').$entry['ip'].((!empty($entry['mask']) || $entry['mask'] == 0) ? '/'.$entry['mask'] : '');
                }
            }
            (new UserEntity())->update($login, [
                'ipv4' => implode(',', $user_ipv4),
                'ipv6' => implode(',', $user_ipv6)
            ]);
        }
        return $this->index('users', array_merge($result, $this->dataProfile($login)));
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
            if (in_array($operation, ['create','uodate','delete'])) {
                $context = $this->resetContext($context);
                if (is_array($context)) {
                    Zord::saveConfig('context', $context);
                } else {
                    $this->response = 'DATA';
                    return $this->error(500, $context);
                }
            }
        }
        return $this->index('context', $result);
    }
    
    public function urls() {
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
            $context = $this->resetContext($context);
            if (is_array($context)) {
                Zord::saveConfig('context', $context);
            } else {
                $this->response = 'DATA';
                return $this->error(500, $context);
            }
        }
        return $this->index('context', $this->dataURLs($name));
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
                    ),
                    'holder'  => $this->contentHolder($name)
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
    
    public function contentHolder($name) {
        return '';
    }
    
    public function users() {
        $data = $this->cursor($this->dataUsers());
        return $this->view('/portal/widget/list', Zord::listModels('users', $data['users']), 'text/html;charset=UTF-8', false, false, 'admin');
    }
    
    protected function usersCriteria($keyword) {
        if (isset($keyword)) {
            $match = '%'.$keyword.'%';
            return [null, [
                'raw'        => 'login LIKE ? OR email LIKE ? OR name LIKE ?',
                'parameters' => [$match, $match, $match]
            ]];
        }
        return [null, null];
    }
    
    protected function dataUsers() {
        $limit = Zord::value('admin', ['users','list','items','limit']) ?? 10;
        $offset = $this->params['offset'] ?? 0;
        $keyword = $this->params['keyword'] ?? null;
        list($join, $where) = $this->usersCriteria($keyword);
        $criteria = ['many' => true, 'join' => $join, 'where' => $where];
        $entities = (new UserEntity())->retrieve($criteria);
        $count = $entities->count();
        $criteria['limit']  = $limit;
        $criteria['offset'] = $offset;
        $users = (new UserEntity())->retrieve($criteria);
        $index = [];
        foreach ($entities as $user) {
            $index[] = $user->login;
        }
        return [
            'list'    => 'users',
            'count'   => $count,
            'limit'   => $limit,
            'offset'  => $offset,
            'index'   => $index,
            'keyword' => $keyword,
            'users'   => $users
        ];
    }
    
    protected function prepareIndex($current, $models) {
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
        } else if ($current == 'users' && !isset($models['login"'])) {
            $models = Zord::array_merge($models, $this->dataUsers());
        } else if ($current == 'context' && !isset($models['ctx'])) {
            foreach(Zord::getConfig('context') as $name => $config) {
                if ($this->user->hasRole('admin', $name)) {
                    $models['data'][] = [
                        'name'  => $name,
                        'title' => isset($config['title'][$this->lang]) ? $config['title'][$this->lang] : (isset($config['title'][DEFAULT_LANG]) ? $config['title'][DEFAULT_LANG] : '')
                    ];
                }
            }
        }
        return $models;
    }
    
    protected function updateModels($models) {
        return $models;
    }
    
    protected function dataProfile($login) {
        $result = [];
        $user = User::get($login);
        $result['login'] = $login;
        $result['name'] = $user->name;
        $result['data']['ipv4'] = $this->explodeIP($user->ipv4);
        $result['data']['ipv6'] = $this->explodeIP($user->ipv6);
        $result['data']['roles'] = [];
        foreach ((new UserHasRoleEntity())->retrieve(['where' => ['user' => $login], 'many' => true]) as $entry) {
            if ($entry->context == '*' || null !== Zord::value('context', $entry->context)) {
                $result['data']['roles'][] = [
                    'context' => $entry->context,
                    'role'    => $entry->role,
                    'start'   => $entry->start,
                    'end'     => $entry->end
                ];
            }
        }
        $roles = array_merge(Zord::getConfig('role'), ['*']);
        $result['choices']['role'] = array_combine($roles, $roles);
        $contexts = array_merge(array_keys(Zord::getConfig('context')), ['*']);
        $result['choices']['context'] = array_combine($contexts, $contexts);
        $result['choices']['include'] = [
            "1" => $this->locale->tab->users->include,
            "0" => $this->locale->tab->users->exclude
        ];
        return $result;
    }
    
    protected function dataURLs($name) {
        $data = [];
        foreach (Zord::value('context', [$name,'url']) ?? [] as $url) {
            $data[] = [
                'secure' => ($url['secure'] ?? false) ? 'true' : 'false',
                'host'   => $url['host'],
                'path'   => $url['path']
            ];
        }
        return [
            'ctx'  => $name,
            'data' => $data
        ];
    }
    
    protected function explodeIP($ips) {
        $result = array();
        if ($ips) {
            $ips = explode(',', $ips);
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
    
    protected function resetContext($context) {
        $result = [];
        foreach ($context as $name => $entry) {
            $result[$name]['__RESET__'] = $entry; 
        }
        return $result;
    }
}

?>