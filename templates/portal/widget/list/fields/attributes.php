<?php foreach (Zord::value('portal', ['list','attributes']) as $attribute => $scopes) { ?>
<?php	if (isset($options[$attribute]) && $options[$attribute] !== false && in_array(isset($entry) ? 'update' : 'create', $scopes)) { ?>
								       <?php echo $attribute.($options[$attribute] === true ? '' : '="'.$options[$attribute].'"')."\n"; ?>
<?php	} ?>
<?php } ?>
