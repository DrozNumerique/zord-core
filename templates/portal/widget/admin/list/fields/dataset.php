<?php foreach (Zord::value('portal', ['list','dataset']) as $name => $default) { ?>
								       data-<?php echo $name ?>="<?php echo $options[$name] ?? $default; ?>"
<?php } ?>
