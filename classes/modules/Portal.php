<?php

class Portal extends Module {
    
    public function home() {
        return $this->page('home');
    }
    
    public function config() {
        $config = Zord::getConfig('portal');
        foreach (Zord::getConfig('lang') as $lang => $label) {
            $config['locales'][$lang] = Zord::objectToArray(Zord::getLocale('portal', $lang));
            $config['locales'][$lang]['label'] = $label;
        }
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            $config['baseURL'][$name] = Zord::getContextURL($name);
        }
        return $config;
    }
}

?>
