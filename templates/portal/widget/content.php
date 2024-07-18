<?php if (!empty($content) || ($editable ?? false)) { ?>
<div class="content widget<?php echo $editable ? ' editable' : ''; ?><?php echo !empty($class) ? ' '.implode(' ', $class) : ''; ?>" data-name="<?php echo $name; ?>">
	<div id="<?php echo $id ?>" class="content display<?php echo !empty($class) ? ' '.implode(' ', $class) : ''; ?>"><?php echo !empty($content) ? Zord::md2html($content) : ($holder ?? ''); ?></div>
<?php   if ($editable) { ?>
    <div class="content editor">
    	<textarea class="content text"><?php echo $content ?? ''; ?></textarea>
    	<button class="content save" data-module="<?php echo $module ?? 'Admin'; ?>" data-action="<?php echo $action ?? 'content'; ?>"><?php echo $locale->save; ?></button>
    	<button class="content cancel"><?php echo $locale->cancel; ?></button>
    </div>
<?php   } ?>
</div>
<?php } ?>
