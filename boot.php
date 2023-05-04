<?php
define('DS', DIRECTORY_SEPARATOR);
$root = null;
$here = 'NULL';
if (isset($_SERVER['PWD'])) {
    $root = dirname($_SERVER['PHP_SELF']);
    if ($root === '.') {
        $root = $_SERVER['PWD'];
    }
} else if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $root = $_SERVER['DOCUMENT_ROOT'];
    $root = substr($root, 0, strpos($root, '/web'));
}
define('ROOT', $root.DS);
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
