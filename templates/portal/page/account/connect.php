<?php $this->render('/portal/page/account/fields/login'); ?>
<?php $this->render('/portal/page/account/fields/password'); ?>
<?php foreach ($models['actions'] ?? Account::actions(false) as $action) { ?>
<?php   if ($action !== 'connect') { ?>
<div class="switch" data-action="<?php echo $action; ?>"><?php echo $locale->switch->$action; ?></div>
<?php   } ?>
<?php } ?>
