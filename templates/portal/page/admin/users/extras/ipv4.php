						<div class="admin-panel-title"><?php echo $locale->tab->users->ipv4; ?></div>
						<div data-update="ipv4">
<?php $this->render('/portal/widget/list', Zord::listModels('ipv4', ['data' => $models['ipv4'], 'choices' => $choices])); ?>
						</div>
