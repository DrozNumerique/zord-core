						<div class="admin-panel-title"><?php echo $locale->tab->users->ipv6; ?></div>
						<div data-update="ipv6">
<?php $this->render('/portal/widget/list', Zord::listModels('ipv6', ['data' => $models['ipv6'], 'choices' => $choices])); ?>
						</div>
