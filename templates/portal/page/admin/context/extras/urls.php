   				<ul class="list admin urls" id="urls">
    					<li class="header">
                      		<div class="column secure"><a class="fa fa-lock fa-fw" title="<?php echo $locale->list->fields->secure; ?>"></a></div>
           					<div class="column host"><?php echo $locale->list->fields->host; ?></div>
           					<div class="column path"><?php echo $locale->list->fields->path; ?></div>
             				<div class="column action add"><a class="fa fa-plus fa-fw" title="<?php echo $locale->list->actions->add; ?>"></a></div>
         				</li>
         				<li class="hidden">
         					<div class="column secure state">
          						<input name="secure" data-empty="no" type="hidden" value="false"/>
          						<i class="display fa fa-chain-broken fa-fw"></i>
         					</div>
          					<div class="column host"><input name="host" data-empty="no" type="text"/></div>
          					<div class="column path"><input name="path" data-empty="no" type="path"/></div>
             				<div class="column action remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->actions->remove; ?>"></i></div>
         				</li>
<?php foreach ($models['urls'] ?? [] as $url) { ?>
<?php   $secure = ($url['secure'] ?? false) ? 'true' : 'false'; ?>
         				<li class="data">
         					<div class="column secure state">
          						<input name="secure" data-empty="no" type="hidden" value="<?php echo $secure; ?>"/>
          						<i class="display fa fa-fw <?php echo Zord::value('portal', ['states','secure',$secure]); ?>"></i>
         					</div>
           					<div class="column host"><input name="host" data-empty="no" type="text" value="<?php echo $url['host']; ?>"/></div>
           					<div class="column path"><input name="path" data-empty="no" type="text" value="<?php echo $url['path']; ?>"/></div>
             				<div class="column action remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->actions->remove; ?>"></i></div>
         				</li>
<?php } ?>
     				</ul>
