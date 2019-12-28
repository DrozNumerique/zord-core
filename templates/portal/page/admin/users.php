<?php
if (isset($models['account'])) {
    $this->render('profile');
} else {
    $this->render('list');
}
?>
