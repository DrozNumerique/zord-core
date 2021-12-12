<?php foreach (Zord::value('portal', ['list','attributes']) as $attribute) { ?>
<?php	if (isset($options[$attribute]) && $options[$attribute] !== false) { ?>
								       <?php echo $attribute.($options[$attribute] === true ? '' : '="'.$options[$attribute].'"')."\n"; ?>
<?php	} ?>
<?php } ?>
