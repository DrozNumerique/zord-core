<?php
class UserHasIPV4Entity extends UserHasIPEntity {
    
    public function match($ip, $include = true) {
        return $this->retrieve(
            [
                'where' => [
                    'raw' => 'ip = INET_ATON(?) & (-1 << (32 - mask)) AND include = ?',
                    'parameters' => array($ip, $include)
                ],
                'many' => true
            ]
        );
    }
}
?>
