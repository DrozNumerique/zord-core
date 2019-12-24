<?php

class Portal extends Module {
    
    public function home() {
        return $this->page('home');
    }
    
    public function chosen() {
        return Zord::getConfig('chosen');
    }
}

?>
