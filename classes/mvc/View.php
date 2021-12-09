<?php

class View {
    
    private $template  = null;
    private $models    = [];
    private $controler = null;
    private $context   = null;
    private $lang      = DEFAULT_LANG;
    private $locales   = [];
    private $mark      = true;
    private $implicits = [];
    
    public function __construct($template, $models, $controler = null, $locale = null) {
        $this->template  = $template;
        $this->models    = $models;
        $this->controler = $controler;
        if ($this->controler) {
            $this->implicits = $this->controler->implicits();
            $this->context   = $this->controler->getContext();
            $this->lang      = $this->controler->getLang();
        }
        array_push($this->locales, $locale ?? $this->getLocale($template));
    }
    
    public function render($template = null, $models = null, $locale = null) {
        if ($template == null) {
            ob_start();
            $template = $this->template;
        }
        $template = $this->getTemplate($template);
        $models = $models ?? $this->models;
        array_push($this->locales, $locale ?? $this->getLocale($template));
        $locale  = $locale ?? end($this->locales);
        $context = $this->context;
        $lang    = $this->lang;
        $locale  = is_string($locale) ? Zord::getLocale($locale, $this->lang) : $locale;
        $page    = null;
        foreach ([$this->implicits, $models] as $_vars) {
            if (is_array($_vars) && Zord::is_associative($_vars)) {
                foreach ($_vars as $_name => $_value) {
                    if (!is_object($_value) && $_name == 'locale') {
                        $_value = json_decode(Zord::json_encode($_value));
                    }
                    $$_name = $_value;
                }
            }
        }
        $this->viewPlugin($template, $models, 'before', $page);
        if (!$this->viewPlugin($template, $models, 'instead', $page)) {
            $_file = Zord::template($template, $context, $lang);
            $_begin = VIEW_MARK_BEGIN;
            $_end = VIEW_MARK_END;
            if (strpos($template, '/script/') > 0 || strpos($template, '/style/') > 0) {
                $_begin = '/*# ';
                $_end = ' #*/';
            }
            $instruction = Zord::value('plugin',['view',$template,'instruction']);
            if (isset($instruction)) {
                echo $instruction."\n";
            }
            $this->mark('BEGIN '.$template, $_begin, $_end);
            if ($_file) {
                include($_file);
            }
            $this->mark('END '.$template, $_begin, $_end);
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
    
    public function locale($domain) {
        return Zord::getLocale($domain, $this->lang);
    }
    
    public function setMark($mark = true) {
        $this->mark = $mark;
    }
    
    public function mark($content, $begin = VIEW_MARK_BEGIN, $end = VIEW_MARK_END) {
        if ($this->mark) {
            echo Zord::mark($content, $begin, $end)."\n";
        }
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