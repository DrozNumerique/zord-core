<?php $content  = Zord::content($name, $lang); ?>
<?php if (isset($content)) { ?>
<?php   $content = file_get_contents($content); ?>
<?php } ?>
<?php if (!empty($content) || ($editable ?? false)) { ?>
<div class="content widget <?php echo $editable ? 'editable' : ''; ?>" data-name="<?php echo $name; ?>">
	<div id="<?php echo $id ?>" class="content display<?php echo implode(' ', $class ?? []); ?>"><?php echo !empty($content) ? Zord::md2html($content) : $holder; ?></div>
<?php   if ($editable) { ?>
    <div class="content editor">
    	<textarea class="content text"><?php echo $content ?? ''; ?></textarea>
    	<button class="content save"><?php echo $locale->save; ?></button>
    	<button class="content cancel"><?php echo $locale->cancel; ?></button>
    </div>
<?php   } ?>
</div>
<?php } ?>
