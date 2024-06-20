<?php

class Admin extends Module {
        
    public function index($current = null, $models = null) {
        $tabs = Zord::getConfig('admin');
        foreach ($tabs as $name => $tab) {
            if (!$this->isAvailable($name, $tab['scope'] ?? 'global')) {
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
                        $result = $this->accountExtrasData(User::get($login));
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
        $login = $this->params['login'] ?? null;
        if (!isset($login)) {
            return $this->account();
        }
        if ((new UserEntity())->retrieveOne($login) === false) {
            return $this->account();
        }
        if ($this->params['update'] ?? false) {
            $user = User::get($login);
            $result = $this->updateProfile($user);
            $profile = $user->lastProfile();
            $profile = isset($profile) ? Zord::objectToArray($profile->profile) : [];
            $profile = $this->enhanceProfile($user, array_merge($profile, $result));
            unset($profile['password']);
            (new UserEntity())->update($user->login, $profile);
        }
        return $this->index('users', array_merge($result, $this->accountExtrasData(User::get($login))));
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
                case 'up': 
                case 'down': {
                    $names = array_keys($context);
                    if (in_array($name, $names)) {
                        foreach ($names as $_index => $_name) {
                            $context[$_name]['position'] = $context[$_name]['position'] ?? $_index;
                        }
                        $index = $context[$name]['position'];
                        foreach ($names as $_index => $_name) {
                            if ($context[$_name]['position'] === $index - 1) {
                                $before = $_name;
                            }
                            if ($context[$_name]['position'] === $index + 1) {
                                $after = $_name;
                            }
                        }
                        if ($operation === 'up' && $index > 0) {
                            $context[$before]['position'] = $index;
                            $context[$name]['position'] = $index - 1;
                        }
                        if ($operation === 'down' && $index < count($names) - 1) {
                            $context[$after]['position'] = $index;
                            $context[$name]['position'] = $index + 1;
                        }
                    }
                    break;
                }
                case 'urls': {
                    $result = $this->contextExtrasData($name);
                    break;
                }
            }
            if (in_array($operation, ['create','update','delete','up','down'])) {
                if (!$this->doContext($operation, $name, $context)) {
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
            if (!$this->doContext('update', $name, $context)) {
                $this->response = 'DATA';
                return $this->error(500, $context);
            }
        }
        return $this->index('context', $this->contextExtrasData($name));
    }
    
    public function resource() {
        $folder   = $this->params['folder'] ?? '/';
        $replace  = $this->params['replace'] ?? "false";
        $source   = $_FILES['file']['tmp_name'] ?? null;
        $filename = $_FILES['file']['name'] ?? basename($this->params['filename'] ?? '');
        if (substr($folder, -1, 1) !== '/') {
            $folder = $folder.'/';
        }
        if (empty($filename)) {
            return ['KO', "Choissisez un fichier à téléverser", false];
        }
        $target   = STORE_FOLDER.PUBLIC_RESOURCE_BASE.$folder.$filename;
        if (file_exists($target) && $replace === "false") {
            return ['KO', "Le fichier existe déjà.\rSouhaitez-vous le remplacer ?", true];
        }
        if (is_dir($target)) {
            return ['KO', "Le nom du fichier correspond à un répertoire existant", false];
        }
        if (!file_exists(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        if (!empty($source) && !move_uploaded_file($source, $target)) {
            return ['KO', "Le fichier n'a pas pu être téléversé", false];
        }
        if (empty($source)) {
            return ['OK', "Le fichier peut être téléversé", false];
        } else {
            $type = Zord::value('content', pathinfo($filename, PATHINFO_EXTENSION)) ?? 'unknown';
            return ['OK', "Le fichier a été téléversé", $type, '/'.PUBLIC_RESOURCE_BASE.$folder.$filename];
        }
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
        return $this->locale->tab->content->label->$name ?? $name;
    }
    
    public function contentHolder($name) {
        return '';
    }
    
    public function users() {
        $models = $this->cursor($this->dataUsers());
        return $this->view('/portal/widget/list', Zord::listModels('users', $models), 'text/html;charset=UTF-8', false, false, 'admin');
    }
    
    protected function updateProfile($user) {
        $result = [];
        $criteria = [
            'where' => ['user' => $user->login],
            'many' => true
        ];
        if (!empty($this->params['roles'])) {
            $roles = Zord::objectToArray(json_decode($this->params['roles']));
            (new UserHasRoleEntity())->delete($criteria);
            foreach ($roles as $role) {
                foreach (['start','end'] as $limit) {
                    $check = DateTime::createFromFormat('Y-m-d', $role[$limit]);
                    if (!$check || $check->format('Y-m-d') !== $role[$limit]) {
                        $role[$limit] = null;
                    }
                }
                (new UserHasRoleEntity())->create($role);
            }
        }
        if (!empty($this->params['ipv4'])) {
            $ipv4 = Zord::objectToArray(json_decode($this->params['ipv4']));
            (new UserHasIPV4Entity())->delete($criteria);
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
                            'user'    => $user->login,
                            'ip'      => $ip,
                            'mask'    => (!empty($entry['mask']) || $entry['mask'] == 0) ? $entry['mask'] : 32,
                            'include' => $entry['include'] ? 1 : $entry['include']
                        ]);
                    }
                    $result['ipv4'][] = ($entry['include'] ? '' : '~').$entry['ip'].((!empty($entry['mask']) || $entry['mask'] == 0) ? '/'.$entry['mask'] : '');
                }
            }
        }
        if (!empty($this->params['ipv6'])) {
            $ipv6 = Zord::objectToArray(json_decode($this->params['ipv6']));
            (new UserHasIPV6Entity())->delete($criteria);
            foreach ($ipv6 as $entry) {
                $entryOK = true;
                $other = UserHasIPEntity::find($entry['ip']);
                if ($entry['include'] && $other) {
                    $entryOK = false;
                    $result['others'][] = [((new UserEntity())->retrieve($other->user)->name).' ('.$other->user.')', $entry['ip']];
                }
                if ($entryOK) {
                    (new UserHasIPV6Entity())->create([
                        'user'    => $user->login,
                        'ip'      => $entry['ip'],
                        'mask'    => (!empty($entry['mask']) || $entry['mask'] == 0) ? $entry['mask'] : 32,
                        'include' => $entry['include'] ? 1 : $entry['include']
                    ]);
                    $result['ipv6'][] = ($entry['include'] ? '' : '~').$entry['ip'].((!empty($entry['mask']) || $entry['mask'] == 0) ? '/'.$entry['mask'] : '');
                }
            }
        }
        $result['ipv4'] = implode(',', $result['ipv4'] ?? []);
        $result['ipv6'] = implode(',', $result['ipv6'] ?? []);
        return $result;
    }
    
    protected function enhanceProfile($user, $data) {
        return $data;
    }
    
    protected function isAvailable($name, $scope) {
        $scopes = [
            'global'  => '*',
            'context' => $this->context
        ];
        if (!$this->user->hasRole('admin', $scopes[$scope])) {
            return false;
        }
        if ($name == 'content' && empty($this->contentList())) {
            return false;
        }
        return true;
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
        $order = $this->params['order'] ?? 'login';
        $direction = $this->params['direction'] ?? 'asc';
        $keyword = $this->params['keyword'] ?? null;
        list($join, $where) = $this->usersCriteria($keyword);
        $criteria = ['many' => true, 'join' => $join, 'where' => $where, 'order' => [$direction => $order]];
        $entities = (new UserEntity())->retrieve($criteria);
        $count = $entities->count();
        $criteria['limit']  = $limit;
        $criteria['offset'] = $offset;
        $users = (new UserEntity())->retrieve($criteria);
        $index = [];
        foreach ($entities as $user) {
            $index[] = $user->$order;
        }
        return [
            'list'      => 'users',
            'count'     => $count,
            'order'     => $order,
            'direction' => $direction,
            'limit'     => $limit,
            'offset'    => $offset,
            'index'     => $index,
            'keyword'   => $keyword,
            'data'      => $users
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
            $context = Zord::getConfig('context');
            uasort($context, function($first, $second) {
                return ($first['position'] ?? 0) <=> ($second['position'] ?? 0);
            });
            foreach($context as $name => $config) {
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
    
    protected function accountExtrasData($user) {
        $result = [];
        $result['login'] = $user->login;
        $result['name'] = $user->name;
        $result['ipv4'] = $this->explodeIP($user->ipv4);
        $result['ipv6'] = $this->explodeIP($user->ipv6);
        $result['roles'] = [];
        foreach ((new UserHasRoleEntity())->retrieve(['where' => ['user' => $user->login], 'many' => true]) as $entry) {
            if ($entry->context == '*' || null !== Zord::value('context', $entry->context)) {
                $result['roles'][] = [
                    'context' => $entry->context,
                    'role'    => $entry->role,
                    'start'   => $entry->start,
                    'end'     => $entry->end
                ];
            }
        }
        $roles    = array_merge(array_keys(Zord::getConfig('role')),    ['*']);
        $contexts = array_merge(array_keys(Zord::getConfig('context')), ['*']);
        $result['choices']['role']    = array_combine($roles, $roles);
        $result['choices']['context'] = array_combine($contexts, $contexts);
        $result['choices']['include'] = [
            "1" => $this->locale->tab->users->include,
            "0" => $this->locale->tab->users->exclude
        ];
        return $result;
    }
    
    protected function contextExtrasData($name) {
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
    
    protected function doContext($operation, $name, $context) {
        $context = $this->preContext($operation, $name, $context);
        Zord::saveConfig('context', $this->resetContext($context));
        $this->postContext($operation, $name, $context);
        $this->applyContext($context);
        return true;
    }
    
    protected function preContext($operation, $name, $context) {
        return $context;
    }
    
    protected function postContext($operation, $name, $context) {
        return;
    }
    
    protected function applyContext($context) {
        return;
    }
}

?>