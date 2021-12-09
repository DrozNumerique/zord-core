   				<input type="hidden" id="context" value="<?php echo $models['ctx'] ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $locale->tab->context->urls; ?></div> 
<?php $this->render('urls'); ?> 
       				<br/>
     				<br/>
    		        <input id="submit-urls" type="button" class="admin-button" value="<?php echo $locale->tab->context->submit; ?>"/>
     				<br/>
     				<br/>
				</div>     				