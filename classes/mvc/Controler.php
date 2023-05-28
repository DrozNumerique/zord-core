<?php

class Controler {
    
    public static $ZORD_CONTEXT = '__ZORD_CONTEXT__';
    
    protected $user     = null;
    protected $context  = null;
    protected $host     = null;
    protected $scheme   = null;
    protected $indexURL = 0;
    protected $baseURL  = null;
    protected $pathURL  = null;
    protected $query    = null;
    protected $fragment = null;
    protected $base     = null;
    protected $lang     = null;
    protected $locale   = null;
    protected $module   = null;
    protected $action   = null;
    protected $params   = [];
    protected $replay   = false;
    protected $models   = [];
    protected $config   = [];
    protected $skin     = null;
    
    public function getUser() {
        return $this->user;
    }
    
    public function setUser($user) {
        $this->user = $user;
    }
    
    public function getHost() {
        return $this->host;
    }
    
    public function getScheme() {
        return $this->scheme;
    }
    
    public function getContext() {
        return $this->context;
    }
    
    public function getIndexURL() {
        return $this->indexURL;
    }
    
    public function getBaseURL() {
        return $this->baseURL;
    }
    
    public function getPathURL() {
        return $this->pathURL;
    }
    
    public function getQuery() {
        return $this->query;
    }
    
    public function getFragment() {
        return $this->fragment;
    }
    
    public function getBase() {
        return $this->base;
    }
    
    public function getLang() {
        return $this->lang;
    }
    
    public function getParams() {
        return $this->params;
    }
    
    public function isReplay() {
        return $this->replay;
    }
    
    public function setLang() {
        $this->lang = Zord::defineLang();
    }
    
    public function setLocale() {
        $this->locale = Zord::getLocale('portal', $this->lang);
    }
    
    public function dispatch() {
        $scheme = $_SERVER['REQUEST_SCHEME'];
        $host   = $_SERVER['HTTP_HOST'];
        $path   = $_SERVER['REQUEST_URI'];
        UserHasSessionEntity::deleteExpired();
        $this->setUser(User::find());
        $target = $this->getTarget($scheme.'://'.$host.$path);
        $this->setLang();
        $this->setLocale();
        $this->handle($target);
    }
    
    public function handle($target, $replay = false) {
        if ($target) {
            $this->host     = $target['host'];
            $this->scheme   = $target['scheme'];
            $this->context  = $target['context'];
            $this->indexURL = $target['indexURL'];
            $this->pathURL  = $target['pathURL'];
            $this->query    = $target['query'];
            $this->fragment = $target['fragment'];
            $this->baseURL  = $target['baseURL'];
            $this->base     = $target['base'];
            $this->params   = $target['params'] ?? [];
            $this->config   = $target['config'];
            $this->skin     = $target['skin'];
            $this->replay   = $replay;
            if ($this->context && $this->baseURL) {
                $class = Zord::getClassName($target['module']);
                if (class_exists($class)) {
                    $this->module = new $class($this);
                    $plugin = Zord::value('plugin', ['module',$target['module'],$target['action']]);
                    if (method_exists($this->module, $target['action']) || isset($plugin)) {
                        $this->action = $target['action'];
                    }
                }
                if ($this->module && $this->action) {
                    if ($this->isAuthorized($target)) {
                        $type = null;
                        $history = null;
                        $this->configure();
                        $this->actionPlugin('before', $target);
                        $result = $this->module->execute($this->action);
                        $this->actionPlugin('after', $target);
                        if (isset($target['params']['response'])) {
                            $type = $target['params']['response'];
                        } else if (null !== Zord::value('target', [$target['module'], $target['action'], 'response'])) {
                            $type = Zord::value('target', [$target['module'], $target['action'], 'response']);
                        } else if (null !== Zord::value('target', [$target['module'], 'response'])) {
                            $type = Zord::value('target', [$target['module'], 'response']);
                        }
                        if (isset($target['params']['history'])) {
                            $history = ($target['params']['history'] !== 'false');
                        } else if (null !== Zord::value('target', [$target['module'], $target['action'], 'history'])) {
                            $history = Zord::value('target', [$target['module'], $target['action'], 'history']);
                        } else if (null !== Zord::value('target', [$target['module'], 'history'])) {
                            $history = Zord::value('target', [$target['module'], 'history']);
                        }
                        $response = $this->module->getResponse($this->action);
                        $type = $response ?? $type;
                        $type = strtoupper($type ?? 'VIEW');
                        $target['type'] = $type;
                        if ($this->isRedirect($result)) {
                            $this->redirect($result['__uri__']);
                        } else if ($this->isForward($result)) {
                            $this->handle(array_merge($target, $result['__target__']), $result['__replay__']);
                        } else if ($this->isError($result)) {
                            $this->error($result, $type);
                        } else {
                            if (isset($result['__history__'])) {
                                $history = $result['__history__'];
                            }
                            if (!isset($history) || $history === true) {
                                $_SESSION['__ZORD__']['__HISTORY__'][$target['type']][] = $target;
                            }
                            $this->output($result, $type);
                        }
                    } else {
                        $alt = Zord::value('target', [$target['module'],$target['action'],'auth','alt']);
                        if ($alt == null) {
                            $alt = Zord::value('target', [$target['module'],'auth','alt']);
                        }
                        if (is_string($alt)) {
                            $this->redirect($this->baseURL.'/'.$alt);
                        } else if (is_array($alt)) {
                            $this->handle(array_merge($target, $alt));
                        } else {
                            $type = Zord::value('target', [$target['module'], $target['action'], 'response']);
                            if (!isset($type)) {
                                $type = Zord::value('target', [$target['module'], 'response']);
                            }
                            $this->error([
                                '__code__' => 403
                            ], $type ?? 'VIEW');
                        }
                    }
                } else {
                    $this->handle($this->getDefaultTarget());
                }
            }
        }
    }
        
    public function findTarget($host, $path) {
        $target = null;
        foreach (Zord::getConfig('context') as $context => $params) {
            if (isset($params['url'])) {
                foreach ($params['url'] as $index => $config) {
                    if ($this->contextMatches($host, $path, $config)) {
                        $target = [
                            'host'     => $host,
                            'scheme'   => ($config['secure'] ?? false) ? 'https' : 'http',
                            'context'  => $context,
                            'skin'     => Zord::getSkin($context),
                            'indexURL' => $index,
                            'config'   => $params,
                            'prefix'   => $config['path']
                        ];
                        $_SESSION[self::$ZORD_CONTEXT] = $context;
                        break;
                    }
                }
                if ($target) break;
            }
        }
        return $target;
    }
    
    public function getTarget($url, $redirect = false) {
        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }
        $host     = parse_url($url, PHP_URL_HOST);
        $path     = parse_url($url, PHP_URL_PATH);
        $scheme   = parse_url($url, PHP_URL_SCHEME);
        $query    = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $target = $this->findTarget($host, $path);
        if ($target['prefix'] !== '/' && substr($url, strlen($scheme.'://'.$host), strlen($target['prefix'])) !== $target['prefix']) {
            $path = $target['prefix'].$path;
            $url = $scheme.'://'.$host.$path.(isset($query) ? '?'.$query : '').(isset($fragment) ? '#'.$fragment : '');
        }
        if ($target) {
            if ($this->isSecure($target) && $scheme !== 'https') {
                $url = 'https'.substr($url, strlen($scheme));
                header('Location: '.$url, true, 301);
                die();
            }
            $target['pathURL'] = $scheme.'://'.$host.$path;
            $target['query'] = $query;
            $target['fragment'] = $fragment;
            $target['baseURL'] = $scheme.'://'.$host.($target['prefix'] == '/' ? '' : $target['prefix']);
            $target['base'] = $scheme.'://'.$host;
            $target['method'] = $_SERVER["REQUEST_METHOD"];
            $target['params'] = $redirect ? $_GET : array_merge($_GET, $_POST);
            if (isset($target['params']['params'])) {
                foreach (Zord::objectToArray(json_decode($target['params']['params'])) as $key => $value) {
                    if (is_array($value)) {
                        $target['params'][$key] = Zord::json_encode($value, false);
                    } else {
                        $target['params'][$key] = $value;
                    }
                }
                unset($target['params']['params']);
            }
            $target['path'] = explode('/', substr($path, strlen($target['prefix']) + ($target['prefix'] == '/' ? 0 : 1)));
            while (count($target['path']) > 0 && $target['path'][count($target['path']) - 1] == '') {
                array_pop($target['path']);
            }
            if (count($target['path']) > 0 && $target['path'][count($target['path']) - 1] == 'index.php') {
                array_pop($target['path']);
            }
            if (count($target['path']) > 0) {
                $target['path'] = $this->normalizePath($target['path'], $target['params']);
                $shortcut = $this->getShortcut($target['path'][0]);
                if (is_array($shortcut)) {
                    foreach(array_keys($shortcut) as $property) {
                        $target[$property] = $shortcut[$property];
                    }
                    array_shift($target['path']);
                } else {
                    $target['module'] = filter_var($target['path'][0], FILTER_SANITIZE_STRING);
                    array_shift($target['path']);
                    if (count($target['path']) > 0) {
                        $target['action'] = filter_var($target['path'][0], FILTER_SANITIZE_STRING);
                        array_shift($target['path']);
                    }
                }
                if (isset($target['module']) && isset($target['action'])) {
                    $parameters = Zord::value('target', [$target['module'], $target['action'], 'parameters']);
                    if (isset($parameters) && is_array($parameters)) {
                        foreach($parameters as $name => $filter) {
                            if (count($target['path']) > 0) {
                                $value = null;
                                if ($filter == 'PATH') {
                                    $value = implode(DS, array_map('urldecode', $target['path']));
                                    $target['path'] = [];
                                } else if (defined($filter)) {
                                    $value = filter_var(urldecode($target['path'][0]), constant($filter));
                                    array_shift($target['path']);
                                }
                                $target['params'][$name] = $value;
                            }
                        }
                    }
                }
            } else {
                foreach (['module','action'] as $property) {
                    if (isset($target['params'][$property])) {
                        $target[$property] = filter_var($target['params'][$property], FILTER_SANITIZE_STRING);
                        unset($target['params'][$property]);
                    }
                }
            }
            if (!isset($target['module']) || !isset($target['action'])) {
                $target = $this->getDefaultTarget($target);
            }
        }
        return $target;
    }
    
    public function getDefaultTarget($target = null) {
        if (!isset($target)) {
            $target = array(
                'host'     => $this->host,
                'scheme'   => $this->scheme,
                'context'  => $this->context,
                'indexURL' => $this->indexURL,
                'baseURL'  => $this->baseURL,
                'pathURL'  => $this->pathURL,
                'query'    => $this->query,
                'fragment' => $this->fragment,
                'base'     => $this->base,
                'config'   => $this->config,
                'skin'     => $this->skin
            );
        }
        if (defined("DEFAULT_PAGE")) {
            $target['module'] = 'Portal';
            $target['action'] = 'page';
            $target['params']['page'] = DEFAULT_PAGE;
        } else {
            $shortcut = $this->getShortcut('default');
            foreach (array_keys($shortcut) as $property) {
                $target[$property] = $shortcut[$property];
            }
        }
        return $target;
    }
    
    public function implicits() {
        return [
            'controler' => $this,
            'host'      => $this->host,
            'scheme'    => $this->scheme,
            'base'      => $this->base,
            'baseURL'   => $this->baseURL,
            'pathURL'   => $this->pathURL,
            'query  '   => $this->query,
            'fragment'  => $this->fragment,
            'user'      => $this->user,
            'config'    => $this->config,
            'skin'      => $this->skin
        ];
    }
    
    public function models() {
        $models = [
            'portal'    => [
                'module'  => get_class($this->module),
                'action'  => $this->action,
                'params'  => Zord::json_encode($this->params, false),
                'title'   => Zord::portalTitle($this->context, $this->lang),
                'locale'  => Zord::getLocale('portal', $this->lang, true),
                'baseURL' => ['zord' => $this->baseURL],
                'user'    => [
                    'login'   => $this->user->login,
                    'name'    => $this->user->name,
                    'email'   => $this->user->email,
                    'session' => $this->user->session
                ]
            ]
        ];
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            $urls = Zord::value('context', [$name,'url']);
            if (isset($urls)) {
                $models['portal']['baseURL'][$name] = Zord::getContextURL($name);
            }
        }
        return $models;
    }
    
    public function fullPath($path) {
        $urls = Zord::value('context', [$this->context,'url']);
        $host = $urls[$this->indexURL]['host'];
        $scheme = 'http'.($urls[$this->indexURL]['secure'] ?? false ? 's' : '');
        return substr($path, 0, 4) == 'http' ? $path : $scheme.'://'.$host.$path;
    }
    
    protected function configure() {
        $this->module->configure();
    }
    
    protected function normalizePath($path, $params) {
        return $path;
    }
    
    private function getShortcut($name) {
        foreach(Zord::getConfig('target') as $module => $actions) {
            foreach ($actions as $action => $config) {
                $shortcut = $config['shortcut'] ?? [];
                if (!is_array($shortcut)) {
                    $shortcut = [$shortcut];
                }
                if (in_array($name, $shortcut)) {
                    return [
                        'module' => $module,
                        'action' => $action
                    ];
                }
            }
        }
        return null;
    }
    
    private function contextMatches($host, $path, $config) {
        $hostPathsMap = array();
        foreach (Zord::getConfig('context') as $context) {
            if (isset($context['url'])) {
                foreach ($context['url'] as $url) {
                    if ($url['path'] !== '/') {
                        $hostPathsMap[$url['host']][] = $url['path'];
                    }
                }
            }
        }
        if ($host == $config['host']) {
            if ($path == null) {
                $path = '/';
            }
            $pos = $path == '/' ? 1 : strpos($path, '/', 1);
            $start = substr($path, 0, $pos > 0 ? $pos : strlen($path));
            if ($config['path'] !== '/') {
                return $start == $config['path'];
            } else {
                return !isset($hostPathsMap[$host]) || !in_array($start, $hostPathsMap[$host]);
            }
        } else {
            return false;
        }
    }
    
    private function isSecure($target) {
        return Zord::value('context', [$target['context'],'url',$target['indexURL'],'secure']);
    }
    
    private function isAuthorized($target) {
        $auth = Zord::value('target', [$target['module'], $target['action'], 'auth']);
        if (!$auth) {
            $auth = Zord::value('target', [$target['module'], 'auth']);
        }
        if (!$auth) {
            return true;
        }
        if (isset($auth['role']) && isset($this->user)) {
            $roles = $auth['role'];
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            foreach($roles as $role) {
                if ($this->user->hasRole($role, $this->context)) {
                    return true;
                }
            }
        }
        if (isset($auth['privilege']) && isset($this->user)) {
            $privileges = $auth['privilege'];
            if (!is_array($privileges)) {
                $privileges = [$privileges];
            }
            foreach($privileges as $privilege) {
                if ($this->user->isAuthorized($privilege, $this->context)) {
                    return true;
                }
            }
        }
        return false;
    }
	
	private function isRedirect($content) {
	    return (
	        is_array($content) &&
	        isset($content['__redirect__']) &&
	        $content['__redirect__'] &&
	        isset($content['__uri__'])
	    );
	}
	
	private function isForward($content) {
	    return (
	        is_array($content) &&
	        isset($content['__forward__']) &&
	        $content['__forward__'] &&
	        isset($content['__target__'])
	    );
	}
	
	private function isError($content) {
	    return (
	        is_array($content) &&
	        isset($content['__error__']) &&
	        $content['__error__'] &&
	        isset($content['__code__'])
	    );
	}
	
	private function redirect($uri) {
	    $this->sendHeaders($this->status(303), ['Location' => $uri]);
	}
	
	private function error($result, $type) {
	    $status = $this->status($result['__code__']);
	    switch ($type) {
	        case 'DATA':
	        case 'DOWNLOAD': {
	            if (isset($result['__message__'])) {
	                $status['message'] = $result['__message__'];
	            }
	            $this->output($status, 'DATA', $result['__code__']);
	            break;
	        }
	        case 'VIEW': {
	            $this->output([
	                '__template__' => '/portal',
	                '__models__'   => [
	                    'page'    => 'error',
                        'status'  => $status,
	                    'message' => $result['__message__']
                    ]
	            ], 'VIEW', $result['__code__']);
	            break;
	        }
	    }
	}
	
	private function output($result, $type, $code = 200) {
	    $status = $this->status($code);
	    switch ($type) {
	        case 'DATA': {
	            $json = Zord::json_encode($result);
	            if ($this->action !== 'hashKey') {
    	            $key = $this->module->hashKey($this->action);
    	            if ($key) {
    	                Zord::updateConfig('hash', function(&$config) use ($key, $result) {
    	                    $config[$key] = hash('md5', serialize($result));
    	                });
    	            }
	            }
	            $this->sendHeaders($status, [
	                'Content-Type' => 'application/json',
	                'Content-length' => strlen($json)
	            ]);
	            echo $json;
	            break;
	        }
	        case 'DOWNLOAD': {
	            $headers = [];
	            $output = '';
	            if ($result['status'] == 'KO') {
	                $message = $this->locale->download->failed.' '.$result['filename'].' ('.$result['message'].')';
	                if (!$result['async']) {
	                    $this->error([
	                        '__code__' => $result['code'],
	                        '__message__' => $message
	                    ], $type);
	                    break;
	                } else {
    	                $headers['Content-Type'] = 'download/error';
    	                $output = Zord::json_encode([
    	                    'code' => $result['code'],
    	                    'message' => $message
    	                ]);
	                }
	            } else {
	                if (!$result['async']) {
	                    $headers['Content-Disposition'] = 'attachment; filename="'.$result['filename'].'"';
	                    $contentType = Zord::value('content', strtolower(pathinfo($result['filename'], PATHINFO_EXTENSION)));
	                    if ($contentType) {
	                        $headers['Content-Type'] = $contentType;
	                    } else {
	                        $headers['Content-Type'] = 'application/octet-stream';
	                        $headers['Content-Transfer-Encoding'] = 'Binary';
	                    }
	                    $output = $result['content'];
	                } else {
	                    $headers['Content-Type'] = 'application/octet-stream';
	                }
	            }
                $this->sendHeaders($status, $headers);
                echo $output;
	            break;
	        }
	        case 'VIEW': {
	            $content = $result['__type__'] ?? 'text/html;charset=UTF-8';
	            $mark    = $result['__mark__'] ?? true;
	            $locale  = $result['__locale__'] ?? null;
	            $this->sendHeaders($status, [
	                'Content-Type' => $content,
	                'Vary'         => 'Accept'
	            ]);
	            $view = Zord::value('target', [get_class($this->module), $this->action, 'view']);
	            if (!isset($view)) {
	                $view = Zord::getClassName('View');
	            }
	            $template = $result['__template__'];
	            $models   = $result['__models__'];
	            $models   = $this->module->models($models);
	            $portal   = $this->models();
	            foreach ($this->models as $name => $model) {
	                $portal['portal'][$name] = $model;
	            }
	            $models   = Zord::array_merge($portal, $models);
	            $models   = $this->modelsPlugin($models);
	            $view     = new $view($template, $models, $this, $locale);
	            $view->setMark($mark);
    	        echo $view->render();
	            break;
	        }
	    }
	}
	
	private function sendHeaders($status, $headers = []) {
	    header("HTTP/1.0 ".$status['code'].' '.$status['reason']);
	    foreach($headers as $key => $value) {
	        header($key.': '.$value);
	    }
	    if ($this->user->isConnected() || ($this->module->disconnecting ?? false)) {
	       Zord::cookie(User::$ZORD_SESSION, $this->user->session ?? '');
	    }
	}
	
	private function sendDownloadHeaders($status, $filename, $headers) {
	    $contentTypeHeaders = [
	        'Content-Type' => 'application/octet-stream',
	        'Content-Transfer-Encoding' => 'Binary'
	    ];
	    $contentType = Zord::value('content', strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
	    if ($contentType) {
	        $contentTypeHeaders = ['Content-Type' => $contentType];
	    }
	    $headers = array_merge($contentTypeHeaders, $headers);
	    $this->sendHeaders($status, $headers);
	}
	
	private function status($code) {
	    $reason = 'No configuration for status code/reason';
	    $status = Zord::getConfig('status');
	    $code = 'HTTP'.$code;
	    if (count($status) > 0) {
	        if (!key_exists($code, $status)) {
	            end($status);
	            $code = key($status);
	        }
	        $reason = $status[$code];
	    } else {
	        $code = 'HTTP999';
	    }
	    $code = substr($code, strlen('HTTP'));
	    return array(
	        'code'   => $code,
	        'reason' => $reason
	    );
	}
	
	private function actionPlugin($point, $target) {
	    $plugins = Zord::value('plugin',['action',$target['module'],$target['action'],$point]);
	    if (isset($plugins)) {
	        if (!is_array($plugins)) {
	            $plugins = [$plugins];
	        }
	        foreach($plugins as $plugin) {
	            $instance = new $plugin();
	            $instance->handle($target);
	        }
	    }
	}
	
	private function modelsPlugin($models) {
	    $plugins = Zord::value('plugin',['models',get_class($this->module)]);
	    if (isset($plugins)) {
	        if (!is_array($plugins)) {
	            $plugins = [$plugins];
	        }
	        foreach($plugins as $plugin) {
	            $instance = new $plugin();
	            $models = $instance->enhance($models, $this);
	        }
	    }
	    return $models;
	}
	
	public function addModel($name, $model) {
	    $this->models[$name][] = $model;
	}
	
	public function addModels($name, $models) {
	    if (Zord::is_associative($models)) {
	        foreach ($models as $name => $model) {
	            $this->addModels($name, $model);
	        }
	    } else {
	        foreach ($models as $model) {
	            $this->addModel($name, $model);
	        }
	    }
	}
	
	public function addMeta($name, $content, $scheme = null, $lang = null) {
	    $this->addModel('meta', [
	        'name'    => $name,
	        'content' => $content,
	        'scheme'  => $scheme,
	        'lang'    => $lang
	    ]);
	}
	
	public function addScript($src, $type = 'text/javascript') {
	    $this->addModel('scripts', [
	        'type' => $type,
	        'src'  => $src
	    ]);
	}
	
	public function addTemplateScript($template, $type = 'text/javascript') {
	    $this->addModel('scripts', [
	        'type'     => $type,
	        'template' => $template
	    ]);
	}
	
	public function addStyle($href, $media = 'screen', $type = 'text/css') {
	    $this->addModel('styles', [
	        'type'  => $type,
	        'media' => $media,
	        'href'  => $href
	    ]);
	}
	
	public function addTemplateStyle($template, $media = 'screen', $type = 'text/css') {
	    $this->addModel('styles', [
	        'type'     => $type,
	        'media'    => $media,
	        'template' => $template
	    ]);
	}
	
	public function updateGenerated($generated, $template, $sources, $models) {
	    $templateFile = Zord::template($template, $this->context, $this->lang);
	    if (is_string($sources)) {
	        $sources = [$sources];
	    }
	    if (is_array($sources)) {
	        $sources[] = $templateFile;
	    } else {
	        $sources = [$templateFile];
	    }
	    if (Zord::needsUpdate(BUILD_FOLDER.$generated, $sources)) {
	        file_put_contents(BUILD_FOLDER.$generated, (new View($template, $models))->render());
	    }
	}
	
	public function addUpdatedModel($type, $generated, $template, $sources, $models, $pattern, $key) {
	    $this->updateGenerated($generated, $template, $sources, $models);
	    $pattern[$key] = '/build/'.$generated;
	    $this->addModel($type, $pattern);
	}
	
	public function addUpdatedScript($generated, $template, $sources, $models, $type = 'text/javascript') {
	    $pattern = ['type' => $type];
	    $key = 'src';
	    $this->addUpdatedModel('scripts', $generated, $template, $sources, $models, $pattern, $key);
	}
	
	public function addUpdatedStyle($generated, $template, $sources, $models, $media = 'screen', $type = 'text/css') {
	    $pattern = ['type' => $type, 'media' => $media];
	    $key = 'href';
	    $this->addUpdatedModel('styles', $generated, $template, $sources, $models, $pattern, $key);
	}
}
