					<input type="hidden" id="login" value="<?php echo $models['login']; ?>"/>
				<div align="center">
					<div class="admin-panel-title"><?php echo $models['name']; ?></div>
<?php foreach (Zord::value('admin', ['users','extras']) ?? [] as $extra) { ?>
<?php   $this->render($extra); ?>
<?php } ?>
<?php $this->render('profile'); ?>
     				<br/>
     				<br/>
    		        <input id="submit-profile" type="button" class="admin-button" value="<?php echo $locale->tab->users->submit; ?>"/>
     				<br/>
     				<br/>
				</div>
