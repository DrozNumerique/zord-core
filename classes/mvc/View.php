<?php

class View {
    
    private $template  = null;
    private $models    = null;
    private $controler = null;
    private $context   = null;
    private $lang      = DEFAULT_LANG;
    private $locales   = [];
    
    public function __construct($template, $models, $controler = null, $locale = null) {
        $this->template  = $template;
        $this->models    = $models;
        $this->controler = $controler;
        if ($this->controler) {
            $this->context = $this->controler->getContext();
            $this->lang    = $this->controler->getLang();
        }
        array_push($this->locales, $locale ?? $this->getLocale($template));
    }
    
    public function render($template = null, $models = null, $locale = null) {
        if ($template == null) {
            ob_start();
            $template = $this->template;
        }
        $template = $this->getTemplate($template);
        if ($models == null) {
            $models = $this->models;
        }
        array_push($this->locales, $locale ?? $this->getLocale($template));
        $locale  = $locale ?? end($this->locales);
        $context = $this->context;
        $lang    = $this->lang;
        $locale  = is_string($locale) ? Zord::getLocale($locale, $this->lang) : $locale;
        $page    = isset($models['page']) ? $models['page'] : null;
        if ($this->controler) {
            $controler = $this->controler;
            $host      = $this->controler->getHost();
            $scheme    = $this->controler->getScheme();
            $baseURL   = $this->controler->getBaseURL();
            $user      = $this->controler->getUser();
            if ($context !== 'unknown') {
                $config = json_decode(Zord::json_encode(Zord::value('context', $context)));
                $skin   = Zord::getSkin($context);
            }
        }
        if (isset($models['view'])) {
            foreach ($models['view'] as $name => $value) {
                $$name = $value;
            }
        }
        $this->viewPlugin($template, $models, 'before', $page);
        if (!$this->viewPlugin($template, $models, 'instead', $page)) {
            $file = Zord::template($template, $context, $lang);
            if ($file) {
                include($file);
            }
            $this->viewPlugin($template, $models, 'after', $page);
        }
        array_pop($this->locales);
        if ($template == $this->template) {
            return ob_get_clean();
        }
    }
    
    public function value($raw, $models = null, $locale = null) {
        $models = $models ?? $this->models;
        $locale = $locale ?? end($this->locales);
        $locale = is_string($locale) ? Zord::getLocale($locale, $this->lang) : $locale;
        return Zord::resolve($raw, $models, $locale);
    }
    
    public function mark($content) {
        echo Zord::mark($content)."\n";
    }
    
    private function viewPlugin($template, $models, $point, $page = null) {
        $plugins = null;
        if (isset($page)) {
            $plugins = Zord::value('plugin',['view',$page,$template,$point]);
        }
        if (!isset($plugins)) {
            $plugins = Zord::value('plugin',['view',$template,$point]);
        }
        if (isset($plugins)) {
            if (!is_array($plugins)) {
                $plugins = [$plugins];
            }
            foreach($plugins as $plugin) {
                if ($plugin !== 'none') {
                    $locale = null;
                    if (strpos($plugin, ':') > 0) {
                        list($plugin, $locale) = explode(':', $plugin);
                    }
                    $this->render($plugin, $models, $locale);
                }
            }
            return true;
        }
        return false;
    }
    
    private function getLocale($template) {
        $file = Zord::template($template, $this->context, $this->lang);
        $target = null;
        if ($file) {
            $target  = pathinfo($file, PATHINFO_FILENAME);
            $dirname = basename(dirname($file));
            if ($dirname == 'script') {
                $target = basename(dirname(dirname($file)));
            }
            $exists = false;
            foreach (COMPONENT_FOLDERS as $folder) {
                if (file_exists($folder.'locales'.DS.$this->lang.DS.$target.'.json')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $target = null;
            }
        }
        return $target ? $target : end($this->locales);
    }
    
    private function getTemplate($template) {
        if (substr($template, 0, 1) !== '/') {
            $path = substr(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file'], 0, -strlen('.php'));
            if (substr($template, 0, 1) == '#') {
                $path = dirname($path);
                $template = substr($template, 1);
            }
            $path = substr($path, strpos($path, 'templates') + strlen('templates'));
            $path = str_replace(DS, '/', $path);
            $template = $path.'/'.$template;
        }
        return $template;
    }
}

?>