<?php $content = Zord::content($models['name'], $lang); ?>
<div class="content">
<?php echo isset($content) ? $content['html'] : ''; ?>
</div>