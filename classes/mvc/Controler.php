<?php

class Controler {
    
    protected $user = null;
    protected $context = null;
    protected $indexURL = 0;
    protected $baseURL = null;
    protected $lang = null;
    protected $locale = null;
    protected $module = null;
    protected $action = null;
    protected $params = [];
    
    public function getUser() {
        return $this->user;
    }
    
    public function setUser($user) {
        $this->user = $user;
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
    
    public function getLang() {
        return $this->lang;
    }
    
    public function getParams() {
        return $this->params;
    }
    
    public function dispatch() {
        $scheme = $_SERVER['REQUEST_SCHEME'];
        $host   = $_SERVER['HTTP_HOST'];
        $path   = $_SERVER['REQUEST_URI'];
        $this->lang = Zord::defineLang();
        $this->locale = Zord::getLocale('portal', $this->lang);
        UserHasSessionEntity::deleteExpired();
        $this->setUser(User::find());
        $this->handle($this->getTarget($scheme.'://'.$host.$path));
    }
        
    public function findTarget($host, $path) {
        $target = null;
        foreach (Zord::getConfig('context') as $context => $params) {
            if (isset($params['url'])) {
                foreach ($params['url'] as $index => $config) {
                    if ($this->contextMatches($host, $path, $config)) {
                        $target = [
                            'context'  => $context,
                            'indexURL' => $index,
                            'prefix'   => $config['path']
                        ];
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
        $host   = parse_url($url, PHP_URL_HOST);
        $path   = parse_url($url, PHP_URL_PATH);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $target = $this->findTarget($host, $path);
        if ($target) {
            if ($this->isSecure($target) && $scheme !== 'https') {
                $url = 'https'.substr($url, strlen($scheme));
                header('Location: '.$url, true, 301);
                die();
            }
            $target['url'] = $url;
            $target['scheme'] = $scheme;
            $target['baseURL'] = $scheme.'://'.$host.($target['prefix'] == '/' ? '' : $target['prefix']);
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
                                $target['params'][$name] = filter_var($target['path'][0], constant($filter));
                                array_shift($target['path']);
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
                'context'  => $this->context,
                'indexURL' => $this->indexURL,
                'baseURL'  => $this->baseURL
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
    
    public function history($target) {
        $_SESSION['__ZORD__']['__HISTORY__'][$target['type']][] = $target;
    }
    
    public function last($target) {
        $type = isset($target['params']['type']) ? $target['params']['type'] : 'VIEW';
        $target = $this->getDefaultTarget();
        if (isset($_SESSION['__ZORD__']['__HISTORY__'][$type]) && count($_SESSION['__ZORD__']['__HISTORY__'][$type]) > 0) {
            $target = end($_SESSION['__ZORD__']['__HISTORY__'][$type]);
            Zord::log($target);
        }
        $this->handle($target);
    }
    
    public function models() {
        $models = ['portal' => [
            'module' => get_class($this->module),
            'action' => $this->action,
            'params' => Zord::json_encode($this->params, false),
            'title'  => Zord::portalTitle($this->context, $this->lang),
            'locale' => Zord::objectToArray(Zord::getLocale('portal', $this->lang))
        ]];
        $models['baseURL']['zord'] = $this->baseURL;
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            $urls = Zord::value('context', [$name,'url']);
            if (isset($urls)) {
                $models['baseURL'][$name] = Zord::getContextURL($name);
            }
        }
        $models['user'] = [
            'login'   => $this->user->login,
            'name'    => $this->user->name,
            'email'   => $this->user->email,
            'session' => $this->user->session
        ];
        return $models;
    }
    
    protected function normalizePath($path, $params) {
        return $path;
    }
    
    private function getShortcut($name) {
        foreach(Zord::getConfig('target') as $module => $actions) {
            foreach ($actions as $action => $config) {
                if (isset($config['shortcut']) && $config['shortcut'] == $name) {
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
        if (is_array($auth) && (!isset($auth['connect']) || !$auth['connect']))  {
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
        return false;
    }
    
    private function handle($target) {
        if ($target) {
            $this->context  = $target['context'];
            $this->indexURL = $target['indexURL'];
            $this->baseURL  = $target['baseURL'];
            $this->params   = isset($target['params']) ? $target['params'] : [];
            if ($this->context && $this->baseURL) {
                if ($target['module'] == 'Controler') {
                    $action = $target['action'];
                    $this->$action($target);
                    return;
                } else {
                    $class = Zord::getClassName($target['module']);
                    if (class_exists($class)) {
                        $this->module = new $class($this);
                        $plugin = Zord::value('plugin', ['module',$target['module'],$target['action']]);
                        if (method_exists($this->module, $target['action']) || isset($plugin)) {
                            $this->action = $target['action'];
                        }
                    }
                }
                if ($this->module && $this->action) {
                    if ($this->isAuthorized($target)) {
                        $type = null;
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
        				if (null !== $this->module->getResponse($this->action)) {
        				    $type = $this->module->getResponse($this->action);
        				}
        				$type = strtoupper($type !== null ? $type : 'VIEW');
        				$target['type'] = $type;
        				if ($this->isRedirect($result)) {
        				    $this->redirect($result['__uri__']);
        				} else if ($this->isForward($result)) {
        				    $this->handle(array_merge($target, $result['__target__']));
        				} else if ($this->isError($result)) {
        				    $this->error($result, $type);
        				} else {
        				    if (!isset($result['__history__']) || $result['__history__'] !== false) {
        				        $this->history($target);
        				    }
        				    $this->output($result, $type);
        				}   				
        			} else {
        			    $forward = Zord::value('target', [$target['module'],$target['action'],'auth','forward']);
        			    if ($forward == null) {
        			        $forward = Zord::value('target', [$target['module'],'auth','forward']);
        			    }
        			    if (is_array($forward)) {
        			        $forward = array_merge($target, $forward);
        				} else {
        				    $forward = $this->getDefaultTarget();
        				}
        				$this->handle($forward);
        			}
        		} else {
        		    $this->handle($this->getDefaultTarget());
        		}
            }
        }
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
	        case 'DATA': {
	            $this->output($status, $type, $result['__code__']);
	            break;
	        }
	        case 'DOWNLOAD': {
	            $status['alert'] = $result['__message__'];
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
    	                $headers['Content-Type'] = 'application/error';
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
	            $this->sendHeaders($status, [
	                'Content-Type' => isset($result['__type__']) ? $result['__type__'] : 'text/html;charset=UTF-8',
	                'Vary'         => 'Accept'
	            ]);
	            $view = Zord::value('target', [get_class($this->module), $this->action, 'view']);
	            if (!isset($view)) {
	                $view = Zord::getClassName('View');
	            }
	            $template = $result['__template__'];
	            $models   = $result['__models__'];
	            $models   = $this->module->models($models);
    	        $models   = $this->modelsPlugin($models);
    	        echo (new $view($template, $models, $this))->render();
	            break;
	        }
	    }
	}
	
	private function sendHeaders($status, $headers = []) {
	    header("HTTP/1.0 ".$status['code'].' '.$status['reason']);
	    foreach($headers as $key => $value) {
	        header($key.': '.$value);
	    }
	    if (isset($this->user->session)) {
	        setcookie(User::$ZORD_SESSION, $this->user->session, time() + 1200, '/');
	    } else {
	        setcookie(User::$ZORD_SESSION, '', time() - 1200, '/');
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
}
