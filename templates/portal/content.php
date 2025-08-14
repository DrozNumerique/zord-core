<div class="<?php echo !empty($page) ? 'page ' : ''; ?>content static <?php echo $name; ?> <?php echo $type; ?>">
<?php echo Zord::resolve($content ?? '', $models, $locale); ?>
</div>