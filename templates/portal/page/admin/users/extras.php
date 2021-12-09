   				<input type="hidden" id="login" value="<?php echo $models['login']; ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $models['name']; ?></div>
<?php if (isset($models['others'])) { ?>
<?php   foreach ($models['others'] as $other) { ?>
           			<div class="admin-panel-warning"><?php echo $other[0].' '.$locale->tab->users->match.' '.$other[1]; ?></div>
<?php   } ?>
<?php } ?>
           			<div class="admin-panel-title"><?php echo $locale->tab->users->roles; ?></div>
<?php $this->render('roles'); ?>
            		<div class="admin-panel-title"><?php echo $locale->tab->users->ipv4; ?></div>
<?php $this->render('ipv4'); ?>
            		<div class="admin-panel-title"><?php echo $locale->tab->users->ipv6; ?></div>
<?php $this->render('ipv6'); ?>
     				<br/>
     				<br/>
    		        <input id="submit-profile" type="button" class="admin-button" value="<?php echo $locale->tab->users->submit; ?>"/>
     				<br/>
     				<br/>
				</div>
