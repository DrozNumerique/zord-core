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
                        'newline' => $entity->newline == 1,
                        'over'    => $entity->over == 1
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
    
    public function clear() {
        $pid = $this->params['pid'] ?? null;
        $clear = false;
        if (isset($pid)) {
            ProcessExecutor::clear($pid);
            $clear = true;
        }
        return ['clear' => $clear];
    }
    
    public function report($line = null) {
        $line = $line ?? ($this->params['line'] ?? null);
        $report = false;
        if (isset($line)) {
            $line = json_decode($line, true);
            if (!empty($line) && isset($line['process'])) {
                $last = (new ProcessHasReportEntity())->retrieveFirst([
                    'many'  => true,
                    'where' => ['process' => $line['process']],
                    'order' => ['desc' => 'index']
                ]);
                if ($last) {
                    $line['index'] = $last->index + 1;
                    (new ProcessHasReportEntity())->create($line);
                    $report = true;
                }
            }
        }
        return ['report' => $report];
    }
    
    public function kill() {
        $pid = $this->params['pid'] ?? null;
        if ($pid) {
            $entity = (new ProcessEntity())->retrieve($pid);
            if ($entity) {
                $process = Zord::execute('exec', DETECT_PROCESS_COMMAND, ['PID' => $pid]);
                if ($process && !empty($process)) {
                    $process = explode("\n", $process);
                    posix_kill((integer) $process[0], KILL_SIGNAL);
                    $lines = [
                        [
                            'process' => $pid,
                            'style'   => 'info',
                            'indent'  => 0,
                            'message' => '',
                            'newline' => true,
                            'over'    => false
                        ],
                        [
                            'process' => $pid,
                            'style'   => 'error',
                            'indent'  => 0,
                            'message' => Zord::getLocale('portal', $this->lang)->process->stopped,
                            'newline' => true,
                            'over'    => false
                        ],
                        [
                            'process' => $pid,
                            'style'   => 'info',
                            'indent'  => 0,
                            'message' => '',
                            'newline' => true,
                            'over'    => false
                        ]
                    ];
                    foreach ($lines as $line) {
                        $this->report(json_encode($line));
                    };
                    ProcessExecutor::stop($pid, 'killed');
                    return ['kill' => $pid, 'lines' => $lines];
                }
            }
        }
        return ['error' => 'Unknow process '.$pid];
    }
}

?>