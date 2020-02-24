	<input type="hidden" name="update" value="true"/>
<?php if (isset($models['token'])) { ?>
	<input type="hidden" name="token" value="<?php echo $models['token'] ?>"/>
<?php } ?>
	<label><?php echo $locale->fields->name ?></label><input type="text" name="name" value="<?php echo $models['name'] ?? ''; ?>" /><br/>
	<label><?php echo $locale->fields->password ?></label><input type="password" name="password" value="<?php echo $models['password'] ?? ''; ?>" /><br/>
	<label><?php echo $locale->fields->confirm ?></label><input type="password" name="confirm" value="<?php echo $models['password'] ?? ''; ?>" /><br/>
	