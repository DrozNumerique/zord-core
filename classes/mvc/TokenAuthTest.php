<?php

class TokenAuthTest extends ProcessExecutor {
    
    public function execute($parameters = []) {
        echo $this->invoke(
            $parameters['baseURL'], 
            $parameters['path'], 
            $parameters['user'], 
            $parameters['key'], 
            realpath(str_replace('~', $_SERVER['HOME'], OPENSSL_PRIVATE_KEY)), 
            realpath(str_replace('~', $_SERVER['HOME'], OPENSSL_PUBLIC_KEY))
        );
    }
    
    private function invoke($baseURL, $path, $user, $key, $clientPrivateKeyFile, $serverPublicKeyFile) {
        $token = null;
        $crypted = null;
        if (openssl_private_decrypt(base64_decode(file_get_contents($baseURL.'/token/'.$user.'/'.$key)), $token, openssl_pkey_get_private(file_get_contents($clientPrivateKeyFile)))) {
            if (openssl_public_encrypt($token, $crypted, openssl_pkey_get_public(file_get_contents($serverPublicKeyFile)))) {
                return file_get_contents($baseURL.$path.'?__ZORD_TOKEN__='.base64_encode($crypted))."\n";
            }
        }
    }
}

?>