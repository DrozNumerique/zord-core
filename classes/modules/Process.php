<?php

class Process extends Module {
    
    public function status() {
        $pid = null;
        $offset = 0;
        if (isset($this->params['pid'])) {
            $pid = $this->params['pid'];
        }
        if (isset($this->params['offset'])) {
            $offset = $this->params['offset'];
        }
        if ($pid) {
            $entity = (new ProcessEntity())->retrieve(['key' => $pid]);
            $file = LOGS_FOLDER.$pid.'.json';
            if (file_exists($file)) {
                $report = Zord::arrayFromJSONFile($file);
                $report = array_slice($report, $offset);
                if ($entity) {
                    $step = $entity->step;
                    $progress = $entity->progress;
                } else {
                    $step = 'closed';
                    $progress = 100;
                    unlink($file);
                }
                return [
                    'step'     => $step,
                    'progress' => $progress,
                    'report'   => $report
                ];
            }
        }
        return ['error' => 'Unknow process '.$pid];
    }
    
    public function kill() {
        $pid = isset($this->params['pid']) ? $this->params['pid'] : null;
        if ($pid) {
            $entity = (new ProcessEntity())->retrieve(['key' => $pid]);
            if ($entity) {
                $PID = Zord::execute('exec', DETECT_PROCESS_COMMAND, ['PID' => $pid]);
                if ($PID && !empty($PID)) {
                    $PID = explode("\n", $PID);
                    posix_kill((integer) $PID[0], KILL_SIGNAL);
                    (new ProcessEntity())->delete($pid);
                    return ['kill' => $pid];
                }
            }
        }
        return ['error' => 'Unknow process '.$pid];
    }
}

?>