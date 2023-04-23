<?php
require_once('boot.php');
if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 1) {
    $class = Zord::getClassName($_SERVER['argv'][1]);
    if (class_exists($class)) {
        $process = new $class();
        $parameters = [];
        if (count($_SERVER['argv']) > 2) {
            $arg = $_SERVER['argv'][2];
            if (file_exists($arg) && is_file($arg) && is_readable($arg)) {
                $parameters = Zord::arrayFromJSONFile($arg);
            } else {
                $parameters = $process->parameters($arg);
            }
        }
        $process->setUser(isset($parameters['user']) ? $parameters['user'] : 'admin');
        $process->setLang(isset($parameters['lang']) ? $parameters['lang'] : DEFAULT_LANG);
        $begin = date('Y-m-d H:i:s');
        echo '-------------------------------------'."\n";
        echo 'BEGIN @ '.$begin."\n";
        echo '-------------------------------------'."\n";
        echo "\n";
        $process->run($parameters);
        $end = date('Y-m-d H:i:s');
        echo '-------------------------------------'."\n";
        echo 'END @ '.$end."\n";
        echo '-------------------------------------'."\n";
        echo 'DURATION = '.date_diff(date_create($begin),date_create($end))->format('%h Hours %i Minute %s Seconds')."\n";
        echo '-------------------------------------'."\n";
    } else {
        $pid = $_SERVER['argv'][1];
        $entity = (new ProcessEntity())->retrieve($pid);
        if ($entity) {
            $class = $entity->class;
            $process = new $class();
            $process->setId($entity->pid);
            $process->setUser($entity->user);
            $process->setLang($entity->lang);
            $process->run(Zord::objectToArray(json_decode($entity->params ?? '{}')));
            usleep(500000);
            ProcessExecutor::stop($pid);
        }
    }
}
?>