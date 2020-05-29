<?php

class Process extends Module {
    
    public function status() {
        $pid = $this->params['pid'] ?? null;
        $offset = $this->params['offset'] ?? 0;
        if (isset($pid)) {
            $process = (new ProcessEntity())->retrieve($pid);
            if ($process !== false) {
                $report = [];
                $entities = (new ProcessHasReportEntity())->retrieve([
                    'many'  => true,
                    'where' => [
                        'process' => $pid,
                        'index'   => ['>' => $offset]
                    ],
                    'order' => ['asc' => 'index']
                ]);
                foreach ($entities as $entity) {
                    $report[] = [
                        'indent'  => $entity->indent,
                        'style'   => $entity->style,
                        'message' => $entity->message,
                        'newline' => $entity->newline == 1
                    ];
                }
                return [
                    'step'     => $process->step,
                    'progress' => $process->progress,
                    'report'   => $report
                ];
            }
        }
        return ['error' => 'Unknow process '.$pid];
    }
    
    public function kill() {
        $pid = isset($this->params['pid']) ? $this->params['pid'] : null;
        if ($pid) {
            $entity = (new ProcessEntity())->retrieve($pid);
            if ($entity) {
                $process = Zord::execute('exec', DETECT_PROCESS_COMMAND, ['PID' => $pid]);
                if ($process && !empty($process)) {
                    $process = explode("\n", $process);
                    posix_kill((integer) $process[0], KILL_SIGNAL);
                    ProcessExecutor::stop($pid, 'killed');
                    return ['kill' => $pid];
                }
            }
        }
        return ['error' => 'Unknow process '.$pid];
    }
}

?>