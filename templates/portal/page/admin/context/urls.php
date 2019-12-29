   				<input type="hidden" id="context" value="<?php echo $models['context'] ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $locale->tab->context->urls; ?></div> 
    				<ul class="admin-list" id="urls" data-columns="30px,300px,300px">
    					<li class="header">
                      		<div class="column"><i class="fa fa-lock fa-fw" title="<?php echo $locale->tab->context->secure; ?>"></i></div>
           					<div class="column"><?php echo $locale->tab->context->host; ?></div>
           					<div class="column"><?php echo $locale->tab->context->path; ?></div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
         					<div class="column state" data-type="secure">
          						<input name="secure" data-empty="no" type="hidden" value="false"/>
          						<i class="display fa fa-chain-broken fa-fw"></i>
         					</div>
          					<div class="column"><input name="host" data-empty="no" type="text"/></div>
          					<div class="column"><input name="path" data-empty="no" type="path"/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php if (isset($models['urls'])) { ?>
<?php   foreach ($models['urls'] as $url) { ?>
<?php     $secure = (isset($url['secure']) && $url['secure']) ? 'true' : 'false'; ?>
         				<li class="data">
         					<div class="column state" data-type="secure">
          						<input name="secure" data-empty="no" type="hidden" value="<?php echo $secure; ?>"/>
          						<i class="display fa fa-fw <?php echo Zord::value('portal', ['states','secure',$secure]); ?>"></i>
         					</div>
           					<div class="column"><input name="host" data-empty="no" type="text" value="<?php echo $url['host']; ?>"/></div>
           					<div class="column"><input name="path" data-empty="no" type="text" value="<?php echo $url['path']; ?>"/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php   } ?>
<?php } ?>
     				</ul>
      				<br/>
     				<br/>
    		        <input id="submit-urls" type="button" class="admin-button" value="<?php echo $locale->tab->context->submit; ?>"/>
     				<br/>
     				<br/>
				</div>     				