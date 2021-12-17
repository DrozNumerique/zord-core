<?php

class Menu {
    
    protected $controler;
    protected $user;
    protected $context;
    protected $params;
    protected $lang;
    protected $baseURL;
    protected $name;
    protected $config;
    protected $locale;
    
    public function __construct($controler, $name = null) {
        $this->controler = $controler;
        $this->name = $name;
        $this->config = isset($name) ? Zord::value('menu', $name) : Zord::getConfig('menu');
        $this->user = $controler->getUser();
        $this->context = $controler->getContext();
        $this->params = $controler->getParams();
        $this->lang = $controler->getLang();
        $this->baseURL = $controler->getBaseURL();
        $this->locale = Zord::getLocale('portal', $this->lang)->menu;
        $this->locale = isset($name) ? $this->locale->$name : $this->locale;
    }
    
    protected function layout() {
        return $this->config['layout'] ?? array_keys($this->config ?? []);
    }
    
    protected function entry($name) {
        $entry = $this->config[$name];
        $entry['connected'] = $entry['connected'] ?? false;
        $entry['active'] = true;
        if (isset($entry['role']) && !$this->user->hasRole($entry['role'], $this->context)) {
            $entry['active'] = false;
        }
        return $entry;
    }
    
    protected function highlight($name, $subName = null) {
        return ($this->params['menu'] ?? null) == ($name.(isset($subName) ? '/'.$subName : ''));
    }
    
    public static function build($controler, $models, $config = null) {
        $menu = Zord::getInstance('Menu', $controler, $config);
        $data = [];
        foreach ($menu->layout() as $name) {
            $entry = $menu->entry($name);
            if ($entry['active'] === true) {
                list($type, $url, $class, $label, $render) = $menu->point($entry, $name, $models);
                $subMenu  = [];
                if ($type == 'menu' && isset($entry['menu']) && is_array($entry['menu']) && Zord::is_associative($entry['menu'])) {
                    foreach ($entry['menu'] as $subName => $subEntry) {
                        list($subType, $subURL, $subClass, $subLabel, $subRender) = $menu->point($subEntry, $subName, $models);
                        if ($menu->highlight($name, $subName)) {
                            $subClass[] = 'highlight';
                        }
                        $subMenu[] = [
                            'type'   => $subType,
                            'name'   => $subName,
                            'url'    => $subURL,
                            'class'  => $subClass,
                            'label'  => $subLabel,
                            'render' => $subRender
                        ];
                    }
                }
                if ($menu->highlight($name)) {
                    $class[] = 'highlight';
                }
                $data[] = [
                    'type'   => $type,
                    'name'   => $name,
                    'url'    => $url,
                    'class'  => $class,
                    'label'  => $label,
                    'render' => $render,
                    'menu'   => $subMenu
                ];
            }
        }
        return $data;
    }
    
    private function point($entry, $name, $models) {
        $type    = isset($entry['type'])  ? $entry['type']  : 'default';
        $path    = isset($entry['path'])  ? $entry['path']  : ($type == 'shortcut' ? (isset($entry['module']) && isset($entry['action']) ? '/'.$entry['module'].'/'.$entry['action'] : '/'.$name) : ($type == 'page' ? '/page/'.$name : ($type == 'content' ? '/content/'.$name : ($type == 'nolink' ? '#' : ''))));
        $url     = isset($entry['url'])   ? $entry['url']   : ($type == 'menu' ? null : ($path !== '#' ? $this->baseURL : '').$path);
        $url     = $url.'?'.http_build_query(array_merge(['menu' => $name], $entry['params'] ?? []));
        $class   = isset($entry['class']) ? (is_array($entry['class']) ? $entry['class'] : [$entry['class']]) : [];
        $label   = isset($entry['label'][$this->lang]) ? $entry['label'][$this->lang] : (isset($entry['label']) ? $entry['label'] : ($this->locale->$name ?? $name));
        $display = isset($entry['display']) ? (new View($entry['display'], $models, $this->controler))->render() : null;
        $render  = $type == 'nolink' ? 'nolink' : ($type == 'menu' ? 'sub' : 'link');
        return [$type, $url, $class, $display ?? $label, $render];
    }
}

?>