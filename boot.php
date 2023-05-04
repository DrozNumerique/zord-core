<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', ($_SERVER['PWD'] ?? substr($_SERVER['DOCUMENT_ROOT'], 0, strpos($_SERVER['DOCUMENT_ROOT'], '/web'))).DS);
$folders = [];
if (file_exists(ROOT.'components') && is_dir(ROOT.'components')) {
    $folders[] = ROOT;
    $components = glob(ROOT.'components'.DS.'*', GLOB_ONLYDIR);
} else {
    $components = glob(dirname(ROOT).DS.'*', GLOB_ONLYDIR);
}
sort($components);
foreach ($components as $component) {
    $folders[] = $component.DS;
}
define('COMPONENT_FOLDERS', $folders);
require_once(ROOT.'classes'.DS.'Zord.php');
Zord::start();
?>
