<ul>
<?php foreach (($models['actions'] ?? ['profile']) as $action) { ?>
	<li class="account <?php echo $action; ?><?php echo $action == ($models['action'] ?? 'profile') ? ' active' : '' ?>">
		<form class="account" method="post" action="<?php echo $baseURL; ?>">
			<input type="hidden" name="module" value="Account"/>
			<input type="hidden" name="action" value="<?php echo $action; ?>"/>
<?php if (!empty($models['success'])) { ?>
			<input type="hidden" name="success" value="<?php echo $models['success'] ?>"/>
<?php } ?>
<?php if (!empty($models['failure'])) { ?>
			<input type="hidden" name="failure" value="<?php echo $models['failure'] ?>"/>
<?php } ?>
<?php if (!empty($models['token'])) { ?>
			<input type="hidden" name="token" value="<?php echo $models['token'] ?>"/>
<?php } ?>
<?php if (!empty($models['message'])) { ?>
<?php   foreach (explode('|', $models['message']) as $message) { ?>	
			<div><?php echo $message; ?></div><br/>
<?php   } ?>
<?php } ?>
<?php $this->render($action); ?>
<?php if (!in_array($action, ['connect','profile'])) { ?>
			<div class="switch" data-action="connect"><?php echo $locale->switch->connect; ?></div>
<?php } ?>
			<div>
				<input type="submit" name="submit" value="<?php echo $locale->actions->$action ?>"/>
			</div>
		</form>
	</li>
<?php } ?>
</ul>
