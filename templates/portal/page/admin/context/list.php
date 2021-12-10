				<div align="center">
<?php $this->render('/portal/widget/admin/list', Zord::listModels(['context', 'context-'.($user->isManager() ? 'manager' : 'admin')], $data)); ?>
				</div>
