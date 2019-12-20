<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__file__).DS);
$folders = [ROOT];
$components = glob(ROOT.'components'.DS.'*', GLOB_ONLYDIR);
sort($components);
foreach ($components as $component) {
    $folders[] = $component.DS;
}
define('COMPONENT_FOLDERS', $folders);
require_once(ROOT.'classes'.DS.'Zord.php');
Zord::start();
?>
