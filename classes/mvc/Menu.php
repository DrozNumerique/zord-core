<?php

class Menu {
    
    protected $controler;
    protected $user;
    protected $context;
    protected $params;
    protected $lang;
    protected $baseURL;
    
    public function __construct($controler) {
        $this->controler = $controler;
        $this->user = $controler->getUser();
        $this->context = $controler->getContext();
        $this->params = $controler->getParams();
        $this->lang = $controler->getLang();
        $this->baseURL = $controler->getBaseURL();
    }
    
    protected function layout() {
        return Zord::value('menu', 'layout') ?? array_keys(Zord::getConfig('menu') ?? []);
    }
    
    protected function entry($name) {
        $entry = Zord::value('menu', $name);
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
    
    public function build(&$models) {
        $layout = $this->layout();
        foreach ($layout as $name) {
            $entry = $this->entry($name);
            if ($entry['active'] === true) {
                list($type, $url, $class, $label) = $this->point($entry, $name, $models['portal']['locale']['menu'][$name] ?? null, $models);
                $subMenu  = [];
                if ($type == 'menu' && isset($entry['menu']) && is_array($entry['menu']) && Zord::is_associative($entry['menu'])) {
                    foreach ($entry['menu'] as $subName => $subEntry) {
                        list(, $subURL, $subClass, $subLabel) = $this->point($subEntry, $subName, $models['portal']['locale']['menu'][$subName] ?? null, $models);
                        if ($this->highlight($name, $subName)) {
                            $subClass[] = 'highlight';
                        }
                        $subMenu[] = [
                            'name'  => $subName,
                            'url'   => $subURL,
                            'class' => $subClass,
                            'label' => $subLabel
                        ];
                    }
                }
                if ($this->highlight($name)) {
                    $class[] = 'highlight';
                }
                $models['portal']['menu']['link'][] = [
                    'type'  => $type,
                    'name'  => $name,
                    'url'   => $url,
                    'class' => $class,
                    'label' => $label,
                    'menu'  => $subMenu
                ];
            }
        }
    }
    
    private function point($entry, $name, $locale, $models) {
        $type    = isset($entry['type'])  ? $entry['type']  : 'default';
        $path    = isset($entry['path'])  ? $entry['path']  : ($type == 'shortcut' ? (isset($entry['module']) && isset($entry['action']) ? '/'.$entry['module'].'/'.$entry['action'] : '/'.$name) : ($type == 'page' ? '/page/'.$name : ($type == 'content' ? '/content/'.$name : ($type == 'nolink' ? '#' : ''))));
        $url     = isset($entry['url'])   ? $entry['url']   : ($type == 'menu' ? null : ($path !== '#' ? $this->baseURL : '').$path);
        $class   = isset($entry['class']) ? (is_array($entry['class']) ? $entry['class'] : [$entry['class']]) : [];
        $label   = isset($entry['label'][$this->lang]) ? $entry['label'][$this->lang] : (isset($entry['label']) ? $entry['label'] : (isset($locale) ? $locale : $name));
        $display = isset($entry['display']) ? (new View($entry['display'], $models, $this->controler))->render() : null;
        return [$type, $url, $class, $display ?? $label];
    }
}

?>