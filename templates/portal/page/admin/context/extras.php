   				<input type="hidden" id="context" value="<?php echo $models['ctx'] ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $locale->tab->context->urls; ?></div> 
<?php $this->render('/portal/widget/list', Zord::listModels('urls', $models)); ?>
       				<br/>
     				<br/>
    		        <input id="submit-urls" type="button" class="admin-button" value="<?php echo $locale->tab->context->submit; ?>"/>
     				<br/>
     				<br/>
				</div>     				