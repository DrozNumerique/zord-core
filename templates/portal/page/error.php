<div id="error">
<?php if (isset($models['message'])) { ?>
    <span><?php echo $models['message']; ?></span>
<?php } else if (isset($models['status'])) { ?>
    <h1><?php echo $models['status']['code']; ?> <?php echo $models['status']['reason']; ?></h1>
<?php } ?>
</div>
