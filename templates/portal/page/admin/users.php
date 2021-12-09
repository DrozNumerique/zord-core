<?php
if (isset($models['login'])) {
    $this->render('extras');
} else {
    $this->render('list');
}
?>
