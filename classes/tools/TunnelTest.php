<?php

class TunnelTest extends ProcessExecutor {
    
    private $name = null;
    
    public function parameters($string) {
        $this->name = $string;
    }
    
    public function execute($parameters = []) {
        new Tunnel($this->name);
    }
}

?>