   				<input type="hidden" id="login" value="<?php echo $models['login']; ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $models['name']; ?></div>
<?php if (isset($models['others'])) { ?>
<?php   foreach ($models['others'] as $other) { ?>
           			<div class="admin-panel-warning"><?php echo $other[0].' '.$locale->tab->users->match.' '.$other[1]; ?></div>
<?php   } ?>
<?php } ?>
<?php $this->render('roles'); ?>
<?php $this->render('addresses'); ?>
     				<br/>
     				<br/>
    		        <input id="submit-profile" type="button" class="admin-button" value="<?php echo $locale->tab->users->submit; ?>"/>
     				<br/>
     				<br/>
				</div>
