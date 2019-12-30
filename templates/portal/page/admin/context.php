<?php
if (isset($models['ctx'])) {
    $this->render('urls');
} else {
    $this->render('list');
}
?>
