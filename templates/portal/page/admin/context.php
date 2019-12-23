<?php
if (isset($models['context'])) {
    $this->render('urls');
} else {
    $this->render('list');
}
?>
