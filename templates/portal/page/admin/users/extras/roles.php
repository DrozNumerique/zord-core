						<div class="admin-panel-title"><?php echo $locale->tab->users->roles; ?></div>
<?php $this->render('/portal/widget/list', Zord::listModels('roles', ['data' => $models['roles'], 'choices' => $choices])); ?>
