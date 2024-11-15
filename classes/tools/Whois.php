<?php

class Whois extends ProcessExecutor {
    
    public function parameters($string) {
        return ['ips' => explode(' ', $string)];
    }
    
    public function execute($parameters = []) {
        foreach ($parameters['ips'] ?? [] as $ip) {
            $entity = UserHasIPEntity::find($ip);
            echo $ip.' => '.($entity !== false ? $entity->user : 'none')."\n";
        }
    }
    
}

?>
