<?php

class Portal extends Module {
    
    public function chosen() {
        return Zord::getConfig('chosen');
    }
}

?>
