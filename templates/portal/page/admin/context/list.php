				<div align="center">
<?php $this->render('/portal/widget/admin/list', Zord::listModels($current, ['context', $user->isManager() ? 'manager' : 'admin'], $data)); ?>
				</div>
