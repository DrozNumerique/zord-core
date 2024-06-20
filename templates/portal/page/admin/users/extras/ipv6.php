						<div class="admin-panel-title"><?php echo $locale->tab->users->ipv6; ?></div>
<?php $this->render('/portal/widget/list', Zord::listModels('ipv6', ['data' => $models['ipv6'], 'choices' => $choices])); ?>
