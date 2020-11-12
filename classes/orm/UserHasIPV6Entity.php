<?php
class UserHasIPV6Entity extends UserHasIPEntity {
    
    public function match($ip, $include = true) {
        $parts = $this->parts($ip);
        return $parts === false ? false : $this->retrieve(
            [
                'where' => [
                    'raw' => '(part1 & mask1) = (? & mask1) AND (part2 & mask2) = (? & mask2) AND (part3 & mask3) = (? & mask3) AND (part4 & mask4) = (? & mask4) AND include = ?',
                    'parameters' => array($parts[0], $parts[1], $parts[2], $parts[3], $include)
                ],
                'many' => true
            ]
        );
    }
    
    public function create(array $data) {
        $parts = $this->parts($data['ip']);
        $masks = $this->masks($data['mask']);
        for ($index = 0; $index < 4; $index++) {
            $data['part'.($index + 1)] = $parts[$index];
            $data['mask'.($index + 1)] = $masks[$index];
        }
        return parent::create($data);
    }
    
    private function parts($ip) {
        if (strpos($ip, '::') === false) {
            $tokens = explode(':', $ip);
        } else {
            $tokens = explode('::', $ip);
            $left = explode(':', $tokens[0]);
            $right = explode(':', $tokens[1]);
            $middle = [];
            for ($index = 0; $index < 8 - (count($left) + count($right)); $index++) {
                $middle[$index] = '0';
            }
            $tokens = array_merge($left, $middle, $right);
        }
        foreach ($tokens as &$token) {
            $token = str_pad($token, 4, '0', STR_PAD_LEFT);
        }
        $ipv6hex = implode('', $tokens);
        if (strlen($ipv6hex) !== 32) {
            return false;
        }
        $parts = [];
        for ($index = 0 ; $index < 4 ; $index++) {
            $parts[$index] = base_convert(substr($ipv6hex, $index * 8, 8), 16, 10);
        }
        return $parts;
    }
    
    private function masks($mask) {
        $binary = '';
        $data = [];
        $length = 128;
        $tokens = 4;
        for ($digit = 0; $digit < $length; $digit++) {
            $binary .= ($digit < $mask ? '1' : 0);
        }
        for ($index = 0 ; $index < $tokens ; $index++) {
            $data[$index] = base_convert(str_pad(dechex(bindec(substr($binary, $index * ($length / $tokens), $length / $tokens))),  $length / ($tokens * 4), '0'), 16, 10);
        }
        return $data;
    }
}
?>
