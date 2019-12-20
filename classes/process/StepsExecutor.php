<?php

abstract class StepsExecutor extends ProcessExecutor {

    protected $parameters = [];
    protected $steps      = null;
    protected $until      = null;
    protected $continue   = false;
    protected $execute    = true;
    
    protected $count    = 0;
    protected $size     = 0;
    protected $total    = 0;
    protected $progress = 0;
    protected $success  = 0;
    protected $done     = false;
    
    public function execute($parameters = []) {
        try {
            $this->configure($parameters);
            if ($this->beforeSteps()) {
                foreach ($this->books as $isbn) {
                    try {
                        $this->resetStep($isbn);
                        $this->progress(round(100 * $this->progress));
                        $this->step($isbn);
                    } catch (Throwable $thrown) {
                        $this->report(0, 'bold', $thrown);
                        continue;
                    }
                    foreach ($this->steps as $step) {
                        $this->beforeStep();
                        if ($this->handle($this->execute, true, $isbn, $step)) {
                            try {
                                if (method_exists($this, $step)) {
                                    $this->done = $this->$step($isbn);
                                } else {
                                    $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->locale->steps->status->KO), "."), false);
                                    $this->report(0, 'KO', $this->locale->steps->status->KO);
                                    $this->report(2, 'error', $this->locale->steps->status->unknown);
                                    if (!$this->handle($this->continue, false, $isbn, $step)) break;
                                }
                            } catch(Throwable $thrown) {
                                $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->locale->steps->status->KO), "."), false);
                                $this->report(0, 'KO', $this->locale->steps->status->KO);
                                $this->report(2, 'bold', $thrown);
                                $this->done = false;
                                if (!$this->handle($this->continue, false, $isbn, $step)) break;
                            }
                            $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->done ? $this->locale->steps->status->OK : $this->locale->steps->status->KO), "."), false);
                            $this->report(0, $this->done ? 'OK' : 'KO', $this->done ? $this->locale->steps->status->OK : $this->locale->steps->status->KO);
                            if ((!$this->done && !$this->handle($this->continue, false, $isbn, $step)) || $step == $this->until) {
                                break;
                            }
                        }
                    }
                    $this->report();
                    if ($this->done) {
                        $this->success++;
                    } else if (!$this->handle($this->continue, false, $isbn))  {
                        break;
                    }
                }
            }
        } catch (Throwable $thrown) {
            $this->report(0, 'bold', $thrown);
            return;
        }
    }
    
    protected function configure($parameters = []) {
        $this->parameters = $parameters;
        if (isset($parameters['steps'])) {
            $this->steps = $parameters['steps'];
            if (!is_array($this->steps)) {
                $this->steps = [$this->steps];
            }
        }
        if (!isset($this->steps)) {
            $this->steps = Zord::value('import', 'steps');
        }
        if (isset($parameters['until'])) {
            $this->until = $parameters['until'];
        }
        if (isset($parameters['continue'])) {
            $this->continue = $parameters['continue'];
        }
        if (isset($parameters['execute'])) {
            $this->execute = $parameters['execute'];
        }
    }
    
    protected abstract function beforeSteps();
    protected abstract function afterSteps();
    protected abstract function resetStep();
    
    private function handle($operation, $default, $isbn, $step = null) {
        if ($operation === !$default) {
            return !$default;
        } else if (is_array($operation)) {
            if (isset($operation[$isbn])) {
                if ($operation[$isbn] === !$default) {
                    return !$default;
                } else if (is_array($operation[$isbn])) {
                    if (isset($operation[$isbn][$step]) && $operation[$isbn][$step] === !$default) {
                        return !$default;
                    }
                }
            } else if (isset($step) && isset($operation[$step])) {
                if ($operation[$step] === !$default) {
                    return !$default;
                } else if (is_array($operation[$step])) {
                    if (isset($operation[$step][$isbn]) && $operation[$step][$isbn] === !$default) {
                        return !$default;
                    }
                }
            }
        }
        return $default;
    }
}

?>