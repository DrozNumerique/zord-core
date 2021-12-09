<?php
if (isset($models['ctx'])) {
    $this->render('extras');
} else {
    $this->render('list');
}
?>
