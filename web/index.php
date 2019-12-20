<?php
require_once('../boot.php');
$controler = Zord::getClassName('Controler');
$controler = new $controler();
$controler->dispatch();
?>