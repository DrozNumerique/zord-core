<?php

class Menu {
    
    protected $controler;
    
    public function __construct($controler) {
        $this->controler = $controler;
    }
    
    protected function layout() {
        return Zord::value('menu', 'layout') ?? array_keys(Zord::getConfig('menu'));
    }
    
    protected function entry($name) {
        return Zord::value('menu', $name);
    }
    
    public function build(&$models) {
        $layout = $this->layout();
        foreach ($layout as $name) {
            $entry = $this->entry($name);
            if ((!isset($entry['role']) || $this->controler->getUser()->hasRole($entry['role'], $this->controler->getContext())) && (!isset($entry['connected']) || ($this->controler->getUser()->isConnected() && $entry['connected']) || (!$this->controler->getUser()->isConnected() && !$entry['connected']) || $this->controler->getUser()->isManager())) {
                list($type, $url, $class, $label) = $this->point($entry, $name, $models['portal']['locale']['menu'][$name] ?? null, $models);
                $subMenu  = [];
                if ($type == 'menu' && isset($entry['menu']) && is_array($entry['menu']) && Zord::is_associative($entry['menu'])) {
                    foreach ($entry['menu'] as $subName => $subEntry) {
                        list(, $subURL, $subClass, $subLabel) = $this->point($subEntry, $subName, $models['portal']['locale']['menu'][$subName] ?? null, $models);
                        if (($this->params['menu'] ?? null) == $name.'/'.$subName) {
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
                if (($this->params['menu'] ?? null) == $name) {
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
        $path    = isset($entry['path'])  ? $entry['path']  : ($type == 'shortcut' ? (isset($entry['module']) && isset($entry['action']) ? '/'.$entry['module'].'/'.$entry['action'] : '/'.$name) : ($type == 'page' ? '/page/'.$name : ($type == 'content' ? '/content/'.$name : '')));
        $url     = isset($entry['url'])   ? $entry['url']   : ($type == 'menu' ? null : $this->controler->getBaseURL().$path);
        $class   = isset($entry['class']) ? (is_array($entry['class']) ? $entry['class'] : [$entry['class']]) : [];
        $label   = isset($entry['label'][$this->controler->getLang()]) ? $entry['label'][$this->controler->getLang()] : (isset($locale) ? $locale : $name);
        $display = isset($entry['display']) ? (new View($entry['display'], $models, $this->controler))->render() : null;
        return [$type, $url, $class, $display ?? $label];
    }
}

?>