<?php $action = $models['action'] ?? ($user->isConnected() ? 'profile' : 'connect'); ?>
<form class="account" method="post" action="<?php echo $baseURL; ?>">
	<input type="hidden" name="module" value="Account"/>
	<input type="hidden" name="action" value="<?php echo $action; ?>"/>
<?php if (isset($models['lasthref']) && $models['lasthref']) { ?>
	<input type="hidden" name="lasthref" value="<?php echo $models['lasthref'] ?>"/>
<?php } ?>
<?php if (isset($models['message']) && $models['message']) { ?>
<?php   foreach (explode('|', $models['message']) as $message) { ?>	
	<div><?php echo $message; ?></div><br/>
<?php   } ?>
<?php } ?>
<?php $this->render($action); ?>
	<div>
		<input type="submit" name="submit" value="<?php echo $locale->actions->$action ?>"/>
	</div>
<?php if ($action == 'connect') { ?>
	<div><?php echo ACCOUNT_AUTO_CREATE ? $locale->messages->create_account : $locale->messages->forgot_password ; ?></div>
<?php } ?>
</form>
