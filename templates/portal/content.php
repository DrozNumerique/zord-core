<?php $content = Zord::content($models['name'], $lang); ?>
<div class="content">
<?php echo isset($content) ? Zord::md2html(file_get_contents($content)) : ''; ?>
</div>