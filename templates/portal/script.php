<?php 
if (isset($skin->scripts)) {
    foreach ($skin->scripts as $script) {
        if (isset($script->src)) {
?>
	<script type="<?php echo $script->type; ?>" src="<?php echo $script->src.Zord::clientCacheQuery(); ?>"></script>
<?php
        } else if (isset($script->template)) {
?>
	<script type="<?php echo $script->type; ?>">
	<!--
<?php
        $this->render($script->template);
?>
	-->
	</script>
<?php
        }
    }
}
?>
<?php 
if (isset($models['portal']['scripts'])) {
    foreach ($models['portal']['scripts'] as $script) {
        if (isset($script['src'])) {
?>
	<script type="<?php echo $script['type']; ?>" src="<?php echo $script['src'].Zord::clientCacheQuery(); ?>"></script>
<?php
        } else if (isset($script['template'])) {
        ?>
	<script type="<?php echo $script['type']; ?>">
	<!--
<?php
        $this->render($script['template']);
?>
	-->
	</script>
<?php
        }
    }
}
?>
