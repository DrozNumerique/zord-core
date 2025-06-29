<?php

class Tunnel {
    
    protected $host = null;
    protected $port = null;
    protected $user = null;
    protected $connection = null;
    protected $sftp       = null;
    protected $process    = null;
    protected $prefix     = null;
    
    public function __construct($name, $process = null, $check = true) {
        $config = Zord::value('connection', ['tunnel', $name]);
        $processUser = posix_getpwuid(posix_geteuid());
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? 22;
        $this->methods = $config['methods'] ?? null;
        $this->user = $config['user'] ?? $processUser['name'];
        $this->prefix = '//'.$this->user.'@'.$this->host.':'.$this->port;
        $this->process = $process;
        $this->connection = ssh2_connect($this->host, $this->port, $this->methods);
        if (isset($config['password'])) {
            ssh2_auth_password($this->connection, $this->user, $config['password']);
        } else {
            ssh2_auth_pubkey_file($this->connection, $this->user, Zord::realPath($config['public'] ?? TUNNEL_PUBLIC_KEY), Zord::realPath($config['private'] ?? TUNNEL_PRIVATE_KEY), $config['passphrase'] ?? null);
        }
        if ($config['check'] ?? false && $check) {
            $command = $config['check']['command'] ?? TUNNEL_CHECK_COMMAND;
            $report  = $config['check']['report']  ?? TUNNEL_CHECK_REPORT;
            $this->exec($command, $report);
        }
        $this->sftp = ssh2_sftp($this->connection);
    }
    
    function log($message)  {
        Zord::log('['.$this->prefix.'] '.$message, 'tunnel');
    }
    
    // based on https://www.php.net/manual/fr/function.ssh2-exec.php#125100
    function exec($command, &$out = null, &$err = null) {
        $report = false;
        if (isset($out) && is_int($out)) {
            $report = true;
            $indent = $out;
        }
        $result = false;
        $out = '';
        $err = '';
        $sshout = ssh2_exec($this->connection, $command);
        if ($sshout) {
            $ssherr = ssh2_fetch_stream($sshout, SSH2_STREAM_STDERR);
            if ($ssherr) {
                # we cannot use stream_select() with SSH2 streams
                # so use non-blocking stream_get_contents() and usleep()
                if (stream_set_blocking($sshout, false) && stream_set_blocking($ssherr, false)) {
                    $result = true;
                    # loop until end of output on both stdout and stderr
                    $wait = 0;
                    while (!feof($sshout) or !feof($ssherr)) {
                        # sleep only after not reading any data
                        if ($wait) {
                            usleep($wait);
                        }
                        $wait = 50000; # 1/20 second
                        foreach (['OUT' => $sshout, 'ERR' => $ssherr] as $target => $stream) {
                            if (!feof($stream)) {
                                $content = stream_get_contents($stream);
                                if ($content === false) {
                                    $result = false;
                                    break;
                                }
                                if ($content !== '') {
                                    foreach (explode("\n", $content) as $line) {
                                        if (!empty(trim($line))) {
                                            $this->log('exec('.$command.') => '.$target.': '.$line);
                                            if ($this->process && $report) {
                                                switch ($target) {
                                                    case 'OUT': {
                                                        $this->process->info($indent, $line);
                                                        break;
                                                    }
                                                    case 'ERR': {
                                                        $this->process->error($indent, $line);
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    switch ($target) {
                                        case 'OUT': {
                                            $out .= $content;
                                            break;
                                        }
                                        case 'ERR': {
                                            $err .= $content;
                                            break;
                                        }
                                    }
                                    $wait = 0;
                                }
                            }
                            if ($result === false) {
                                break;
                            }
                        }
                    }
                }
                # we need to wait for end of command
                stream_set_blocking($sshout, true);
                stream_set_blocking($ssherr, true);
                # these will not get any output
                stream_get_contents($sshout);
                stream_get_contents($ssherr);
                fclose($ssherr);
            }
            fclose($sshout);
        }
        return $result;
    }
    
    public function recv($source, $target) {
        return $this->copy($source, $target, 'pull');
    }
    
    public function send($source, $target) {
        return $this->copy($source, $target, 'push');
    }
    
    public function copy($source, $target, $direction) {
        return Zord::handleError([
            'try' => function() use($source, $target, $direction) {
                switch($direction) {
                    case 'pull': {
                        if (!file_exists(dirname($target))) {
                            mkdir(dirname($target), 0777, true);
                        }
                        return ssh2_scp_recv($this->connection, $source, $target);
                    }
                    case 'push': {
                        ssh2_sftp_mkdir($this->sftp, dirname($target), 0777, true);
                        return ssh2_scp_send($this->connection, $source, $target);
                    }
                }
            },
            'catch' => function($exception) use ($source, $target, $direction) {
                switch($direction) {
                    case 'pull': {
                        $source = $this->prefix.(substr($source, 0, strlen(DS)) == DS ? $source : DS.'~'.DS.$source);
                        break;
                    }
                    case 'push': {
                        $target = $this->prefix.(substr($target, 0, strlen(DS)) == DS ? $target : DS.'~'.DS.$target);
                        break;
                    }
                }
                $this->log('Error while copying '.$source.' to '.$target);
                $this->log($exception->getMessage());
                return false;
            }
        ]);
    }
    
    public function connection() {
        return $this->connection;
    }
    
}

?>