<?php
abstract class ProcessPlugin {
    
    protected $invoker = null;
    protected $user    = null;
    
    public function __construct($invoker) {
        $this->invoker = $invoker;
        $this->user    = $invoker->getUser();
    }
    
    public abstract function execute($parameters);
    
    public function step($step) {
        $this->invoker->step($step);
    }
    
    public function progress($progress) {
        $this->invoker->progress($progress);
    }
    
    public function report($indent = 0, $style = 'default', $message = '', $newline = true) {
        $this->invoker->report($indent, $style, $message, $newline);
    }
    
    public function getLang() {
        return $this->invoker->getLang();
    }
    
    public function info($indent = 0, $message = '') {
        $this->invoker->info($indent, $message);
    }
    
    public function warn($indent = 0, $message = '') {
        $this->invoker->warn($indent, $message);
    }
    
    public function error($indent = 0, $message = '') {
        $this->invoker->error($indent, $message);
    }
}
?>