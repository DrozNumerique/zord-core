<?php
if (isset($models['login'])) {
    $this->render('profile');
} else {
    $this->render('list');
}
?>
