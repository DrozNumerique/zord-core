<?php

class Module {
        
    protected $params = null;
    protected $context = null;
    protected $indexURL = 0;
    protected $baseURL = null;
    protected $controler = null;
    protected $user = null;
    protected $lang = null;
    protected $locale = null;
    protected $models = [];
    protected $response = null;
    
    public function __construct($controler) {
        $this->controler = $controler;
        if ($controler) {
            $this->context  = $controler->getContext();
            $this->indexURL = $controler->getIndexURL();
            $this->baseURL  = $controler->getBaseURL();
            $this->params   = $controler->getParams();
            $this->user     = $controler->getUser();
            $this->lang     = $controler->getLang();
        }
        $this->locale = Zord::getLocale(strtolower(get_class($this)), $this->lang);
    }
    
    public function getControler() {
        return $this->controler;
    }
    
    public function getResponse($action) {
        return $this->response;
    }
    
    public function hasAccess($action) {
        return true;
    }
    
    public function getLocale() {
        return $this->locale;
    }
    
    public function configure() {
        
    }
    
    public function execute($action) {
        if (!$this->hasAccess($action)) {
            return $this->error(403);
        }
        $plugin = Zord::value('plugin', ['module',get_class($this),$action]);
        if (isset($plugin) && is_string($plugin)) {
            $instance = new $plugin();
            return $instance->handle($this, $action);
        }
        return $this->$action();
    }
    
    public function models($models) {
        $portal = $this->controler->models();
        foreach ($this->models as $name => $model) {
            $portal['portal'][$name] = $model;
        }
        return array_merge($portal, $models);
    }
    
    public function addModel($name, $model) {
        $this->models[$name][] = $model;
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
    
	public function forward($target = array(), $replay = false) {
	    if (count($target) == 0) {
	        $target = $this->controler->getDefaultTarget();
	    }
	    return [
			'__forward__' => true,
		    '__target__' => $target,
	        '__replay__' => $replay
	    ];
	}
	
	public function error($code, $message = null) {
	    return [
	        '__error__' => true,
	        '__code__' => $code,
	        '__message__' => $message
	    ];
	}
	
	public function view($template, $models = [], $type = 'text/html;charset=UTF-8', $history = null) {
	    return [
	        '__template__' => $template,
	        '__models__'   => $models,
	        '__type__'     => $type,
	        '__history__'  => $history
	    ];
	}
	
	public function page($page = null, $models = array()) {
	    $page = $page != null ? $page : $this->params['page'];
	    $template = '/portal/page/'.$page;
	    $this->response = 'VIEW';
	    if (Zord::template($template, $this->context, $this->lang)) {
	        $models['page'] = $page;
	        $types = Zord::value('page', $page);
	        if ($types) {
	            foreach($types as $name => $type) {
	                foreach ($type as $model) {
	                    $this->addModel($name, $model);
	                }
	            }
	        }
	        return $this->view('/portal', $models);
	    } else {
	        return $this->error(404);
	    }
	}
	
	public function send($path, $role) {
	    $file = $this->file($path, $role);
	    if ($file['code'] == 200) {
	        $contentType = Zord::value('content', strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)));
	        return $this->view('/readfile', ['filename' => $file['name']], $contentType);
	    } else {
	        return $this->error($file['code']);
	    }
	}
	
	public function upload($name = null) {
	    $name = $this->either($name, 'name');
	    $locale = Zord::getLocale('portal', $this->lang);
	    $key = ini_get("session.upload_progress.prefix").$name;
	    $percent = 100;
	    $message = $locale->upload->done;
	    if (isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
	        $current = $_SESSION[$key]["bytes_processed"];
	        $total = $_SESSION[$key]["content_length"];
	        $percent = $current < $total ? ceil($current / $total * 100) : 100;
	        $message = $locale->upload->progress;
	    }
	    return array(
	        'percent' => $percent,
	        'message' => $message
	    );
	}
	
	public function download($path = null, $role = null, $content = null) {
	    if ($path != null) {
	        $file = [
	            'name' => $path,
	            'code' => 200,
	            'message' => 'OK'
	        ];
	        if ($content == null) {
	            $file = $this->file($path, $role);
	            if ($file['code'] == 200) {
	               $content = file_get_contents($file['name']);
	            }
	        }
	        $filename = pathinfo($file['name'], PATHINFO_BASENAME);
	        $status = $file['code'] == 200 ? 'OK' : 'KO';
	        $async = (isset($this->params['async']) && $this->params['async']);
	        $_SESSION['__ZORD__']['___DOWNLOAD___'] = [
    	        'filename' => $filename,
    	        'content' => $content,
    	        'status' => $status,
    	        'async' => $async,
    	        'code' => $file['code'],
    	        'message' => $file['message']
    	    ];
	    }
	    if ($path == null) {
	        $_SESSION['__ZORD__']['___DOWNLOAD___']['async'] = false;
	    }
	    $this->response = 'DOWNLOAD';
	    return $_SESSION['__ZORD__']['___DOWNLOAD___'];
	}
	
	public function redirect($url = null, $request = false) {
	    $url =  $this->either($url, 'url');
	    if ($url !== null) {
	        if ($request) {
	            return $this->view('/redirect', ['url' => $url]);
	        } else {
    	        return [
    	            '__redirect__' => true,
    	            '__uri__'      => $url
    	        ];
	        }
	    } else {
	        return $this->error(404);
	    }
	}
	
	public function file($path, $role = null, $messages = null, $params = null) {
	    $compliant = true;
	    if ($messages == null ) {
	        $messages = Zord::getConfig('status');
	    }
	    if ($params == null) {
	        $params = $this->params;
	    }
	    $file = preg_replace_callback(
	        '/\${(\w*)}/',
	        function($matches) use (&$compliant, $params) {
	            $key = $matches[1];
	            if (!isset($params[$key])) {
	                $compliant = false;
	            } else {
	                return $params[$key];
	            }
	        },
	        $path
	    );
	    $code = '520';
	    if ($compliant) {
	        if ($this->user->hasRole($role, $this->context)) {
	            if (file_exists($file)) {
	                $code = '200';
	            } else {
	                $code = '404';
	            }
	        } else if ($this->user->isConnected()) {
	            $code = '403';
	        } else {
	            $code = '401';
	        }
	    } else {
	        $code = '400';
	    }
	    return ['name' => $file, 'code' => $code, 'message' => $messages['HTTP'.$code]];
	}
	
	public function either($value, $key) {
	    return $value !== null ? $value : (isset($this->params[$key]) ? $this->params[$key] : null);
	}
	
	public function sendMail($parameters) {
	    $parameters['controler'] = $this->controler;
	    $parameters['locale'] = $this->locale;
	    return Zord::sendMail($parameters);
	}
}
