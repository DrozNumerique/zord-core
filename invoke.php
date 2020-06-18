<?php
$token = null;
$crypted = null;
if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 4) {
    set_error_handler(
        function ($severity, $message, $file, $line) {
            throw new ErrorException($message, $severity, $severity, $file, $line);
        }
    );
    $tokenURL             = $_SERVER['argv'][1];
    $targetURL            = $_SERVER['argv'][2];
    $clientPrivateKeyFile = $_SERVER['argv'][3];
    $serverPublicKeyFile  = $_SERVER['argv'][4];
    try {
        $token = Zord::decrypt(base64_decode(@file_get_contents($tokenURL)), $clientPrivateKeyFile);
        if ($token !== false) {
            $crypted = Zord::encrypt($token, $serverPublicKeyFile);
            if ($crypted !== false) {
                echo @file_get_contents($targetURL.'?'.User::$ZORD_TOKEN.'='.base64_encode($crypted))."\n";
                exit(0);
            } else {
                exit(1);
            }
        }
    } catch(ErrorException $exception) {
        $trace = $exception->getTrace();
        if ($trace[1]['function'] == 'file_get_contents') {
            $url = $trace[1]['args'][0];
            if (isset($trace[0]['args'][4]['http_response_header'][0])) {
                $status = explode(' ', $trace[0]['args'][4]['http_response_header'][0], 3);
                echo $url.' : '.$status[2]."\n";
                exit((int) $status[1]);
            }
        } else {
            echo $exception->getMessage();
            exit(2);
        }
    }
    restore_error_handler();
} else {
    exit(3);
}
?>