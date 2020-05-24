<?php

abstract class ProcessExecutor {
    
    protected $pid = null;
    protected $parameters = array();
    protected $report = array();
    protected $lang = DEFAULT_LANG;
    protected $locale = null;
    protected $user = null;
    protected $class = null;
    
    public static function start($class, $user, $lang = DEFAULT_LANG, $params = []) {
        $pid = bin2hex(openssl_random_pseudo_bytes(32));
        (new ProcessEntity())->create([
            'pid'    => $pid,
            'class'  => Zord::getClassName($class),
            'user'   => $user,
            'lang'   => $lang,
            'params' => Zord::json_encode($params)
        ]);
        file_put_contents(LOGS_FOLDER.$pid.'.json', '[]');
        Zord::execute('popen', PROCESS_COMMAND, [
            'SCRIPT' => ROOT.'process.php',
            'PID'    => $pid
        ]);
        return $pid;
    }
    
    public static function indent($indent) {
        return Zord::str_pad('', 2 * $indent, ' ');
    }
    
    public static function style($style) {
        return "\x1B[".Zord::value('process', ['styles',$style])."m";
    }
    
    public function setId($pid) {
        $this->pid = $pid;
    }
    
    public function setLang($lang) {
        $this->lang = $lang;
        $this->setLocale(isset($this->class) ? $this->class : get_class($this));
    }
    
    public function setUser($user) {
        $this->user = new User($user);
    }
    
    protected function setLocale($locale) {
        $this->locale = Zord::getLocale($locale, $this->lang);
    }
    
    public function getLang() {
        return $this->lang;
    }
    
    public function getLocale() {
        return $this->locale;
    }
    
    public function getUser() {
        return $this->user;
    }
    
    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }
    
    public function step($step) {
        if ($this->pid) {
            (new ProcessEntity())->update(['key' => $this->pid], ['step' => $step]);
        }
    }
    
    public function progress($progress) {
        if ($this->pid) {
            (new ProcessEntity())->update(['key' => $this->pid], ['progress' => $progress]);
        }
    }
    
    public function report($indent = 0, $style = 'default', $message = '', $newline = true) {
        if ($message instanceof Throwable) {
            $this->report($indent, $style, $message->getMessage());
            foreach (explode("\n", $message->getTraceAsString()) as $trace) {
                $this->report($indent, $style, $trace);
            }
        } else {
            if ($this->pid) {
                $this->report[] = [
                    'indent'  => $indent,
                    'style'   => $style,
                    'message' => $message,
                    'newline' => $newline
                ];
                file_put_contents(LOGS_FOLDER.$this->pid.'.json', Zord::json_encode($this->report));
            } else {
                echo self::indent($indent).self::style($style).$message.self::style('default').($newline ? "\n" : "");
            }
        }
    }
    
    public function info($indent = 0, $message = '', $newline = true) {
        $this->report($indent, 'info', $message, $newline);
    }
    
    public function warn($indent = 0, $message = '', $newline = true) {
        $this->report($indent, 'warn', $message, $newline);
    }
    
    public function error($indent = 0, $message = '', $newline = true) {
        $this->report($indent, 'error', $message, $newline);
    }
    
    public abstract function execute($parameters = []);
    
    public function run($parameters = []) {
        if (!defined('DEBUG') || !constant('DEBUG')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        }
        $this->plugin('before', $parameters);
        $this->execute($parameters);
        $this->plugin('after', $parameters);
    }
    
    protected function plugin($point, $parameters) {
        $class = get_class($this);
        $locale = Zord::getLocale('Process',$this->lang);
        $method = null;
        if (isset($parameters['method'])) {
            $method = $parameters['method'];
            $plugins = Zord::value('plugin',['process',$class,$method,$point]);
        } else {
            $plugins = Zord::value('plugin',['process',$class,$point]);
        }
        if (isset($plugins)) {
            if (!is_array($plugins)) {
                $plugins = [$plugins];
            }
            foreach($plugins as $plugin) {
                $instance = new $plugin($this);
                $this->report(0, 'info', $locale->run.' '.$locale->$point.' '.$class.(isset($method) ? '#'.$method : '').' : '.$plugin);
                $instance->execute($parameters);
                $this->report();
            }
        }
    }
    
    public function sendMail($parameters) {
        $parameters['locale'] = $this->locale;
        return Zord::sendMail($parameters);
    }
}

?>