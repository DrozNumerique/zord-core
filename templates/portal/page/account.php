<ul class="account">
<?php foreach (array_keys($models['switch']) as $index => $action) { ?>
	<li class="account <?php echo $action; ?><?php echo $index == 0 ? ' active' : '' ?>">
		<form data-action="<?php echo $action; ?>" class="account <?php echo $action; ?> <?php echo strtolower($response ?? 'VIEW'); ?>" method="post" action="<?php echo $baseURL; ?>">
			<input type="hidden" name="module" value="Account"/>
			<input type="hidden" name="action" value="<?php echo $action; ?>"/>
			<input type="hidden" name="response" value="<?php echo $response ?? 'VIEW'; ?>"/>
<?php   if (!empty($models['success'])) { ?>
			<input type="hidden" name="success" value="<?php echo $models['success'] ?>"/>
<?php   } ?>
<?php   if (!empty($models['failure'])) { ?>
			<input type="hidden" name="failure" value="<?php echo $models['failure'] ?>"/>
<?php   } ?>
<?php   if (!empty($models['token'])) { ?>
			<input type="hidden" name="token" value="<?php echo $models['token'] ?>"/>
<?php   } ?>
			<div class="title"><?php echo $locale->titles->$action; ?></div>
			<div class="messages">
<?php   if (!empty($message)) { ?>
<?php     $this->render('/portal/widget/message'); ?>
<?php   } ?>
			</div>
<?php   $this->render($action, Zord::array_merge($models, ['switch' => $action])); ?>
<?php   foreach ($models['switch'][$action]['before'] ?? [] as $switch) { ?>
			<div class="switch before" data-action="<?php echo $switch; ?>"><?php echo $locale->switch->$switch; ?></div>
<?php   } ?>
			<div>
				<input type="submit" name="submit" value="<?php echo $locale->actions->$action ?>"/>
			</div>
<?php   foreach ($models['switch'][$action]['after'] ?? [] as $switch) { ?>
			<div class="switch after" data-action="<?php echo $switch; ?>"><?php echo $locale->switch->$switch; ?></div>
<?php   } ?>
		</form>
	</li>
<?php } ?>
</ul>
