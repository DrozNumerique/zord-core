<?php

class Portal extends Module {
    
    public function home() {
        return $this->page('home');
    }
    
    public function config() {
        return Zord::getConfig('portal');
    }
}

?>
