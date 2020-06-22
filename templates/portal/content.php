<?php $content = Zord::content($models['name'], $lang); ?>
<div class="content <?php echo $models['name']; ?>">
<?php echo isset($content) ? Zord::md2html(file_get_contents($content)) : ''; ?>
</div>