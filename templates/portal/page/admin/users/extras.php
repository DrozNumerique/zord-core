   				<input type="hidden" id="login" value="<?php echo $models['login']; ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $models['name']; ?></div>
<?php if (isset($models['others'])) { ?>
<?php   foreach ($models['others'] as $other) { ?>
           			<div class="admin-panel-warning"><?php echo $other[0].' '.$locale->tab->users->match.' '.$other[1]; ?></div>
<?php   } ?>
<?php } ?>
           			<div class="admin-panel-title"><?php echo $locale->tab->users->roles; ?></div>
<?php $this->render('/portal/widget/list', Zord::listModels('roles', ['data' => $models['roles'], 'choices' => $choices])); ?>
            		<div class="admin-panel-title"><?php echo $locale->tab->users->ipv4; ?></div>
<?php $this->render('/portal/widget/list', Zord::listModels('ipv4', ['data' => $models['ipv4'], 'choices' => $choices])); ?>
            		<div class="admin-panel-title"><?php echo $locale->tab->users->ipv6; ?></div>
<?php $this->render('/portal/widget/list', Zord::listModels('ipv6', ['data' => $models['ipv6'], 'choices' => $choices])); ?>
     				<br/>
     				<br/>
    		        <input id="submit-profile" type="button" class="admin-button" value="<?php echo $locale->tab->users->submit; ?>"/>
     				<br/>
     				<br/>
				</div>
