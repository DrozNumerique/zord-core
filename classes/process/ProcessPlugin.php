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
    
    public function report($indent = 0, $style = 'default', $message = '', $newline = true, $pad = false) {
        $this->invoker->report($indent, $style, $message, $newline, $pad);
    }
    
    public function getLang() {
        return $this->invoker->getLang();
    }
    
    public function info($indent = 0, $message = '', $newline = true, $pad = false) {
        $this->invoker->info($indent, $message, $newline, $pad);
    }
    
    public function warn($indent = 0, $message = '', $newline = true, $pad = false) {
        $this->invoker->warn($indent, $message, $newline, $pad);
    }
    
    public function error($indent = 0, $message = '', $newline = true, $pad = false) {
        $this->invoker->error($indent, $message, $newline, $pad);
    }
}
?>