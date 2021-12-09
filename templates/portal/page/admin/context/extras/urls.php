   				<ul class="admin-list" id="urls" data-columns="<?php echo Zord::value('admin', ['context','list','columns','urls']); ?>">
    					<li class="header">
                      		<div class="column"><a class="fa fa-lock fa-fw" title="<?php echo $locale->tab->context->secure; ?>"></a></div>
           					<div class="column"><?php echo $locale->tab->context->host; ?></div>
           					<div class="column"><?php echo $locale->tab->context->path; ?></div>
             				<div class="column add"><a class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></a></div>
         				</li>
         				<li class="hidden">
         					<div class="column state" data-type="secure">
          						<input name="secure" data-empty="no" type="hidden" value="false"/>
          						<i class="display fa fa-chain-broken fa-fw"></i>
         					</div>
          					<div class="column"><input name="host" data-empty="no" type="text"/></div>
          					<div class="column"><input name="path" data-empty="no" type="path"/></div>
             				<div class="column remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php foreach ($models['urls'] ?? [] as $url) { ?>
<?php   $secure = ($url['secure'] ?? false) ? 'true' : 'false'; ?>
         				<li class="data">
         					<div class="column state" data-type="secure">
          						<input name="secure" data-empty="no" type="hidden" value="<?php echo $secure; ?>"/>
          						<i class="display fa fa-fw <?php echo Zord::value('portal', ['states','secure',$secure]); ?>"></i>
         					</div>
           					<div class="column"><input name="host" data-empty="no" type="text" value="<?php echo $url['host']; ?>"/></div>
           					<div class="column"><input name="path" data-empty="no" type="text" value="<?php echo $url['path']; ?>"/></div>
             				<div class="column remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php } ?>
     				</ul>
