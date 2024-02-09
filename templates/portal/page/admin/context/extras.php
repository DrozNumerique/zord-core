				<div align="center">
					<div class="admin-panel-title"><?php echo $locale->tab->context->urls; ?></div>
					<form id="data" method="POST" action="/Admin/urls" enctype="multipart/form-data">
						<input type="hidden" id="context" name="ctx"  value="<?php echo $models['ctx'] ?>"/>
						<input type="hidden" id="urls"	name="urls" value=""/>
<?php $this->render('/portal/widget/list', Zord::listModels('urlsList', $models)); ?>
<?php $this->render('/portal/page/admin/context/extras/post'); ?>
    					<br/>
    					<br/>
    					<input id="submit-data" type="submit" class="admin-button" value="<?php echo $locale->tab->context->submit; ?>"/>
    					<br/>
    					<br/>
					</form>
				</div>