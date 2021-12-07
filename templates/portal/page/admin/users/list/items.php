<?php $locale = Zord::getLocale('admin', $lang); ?> 
   					<ul class="admin-list" id="users" data-columns="<?php echo Zord::value('admin', ['users','list','columns','items']); ?>">
   						<li class="header">
           					<div class="column"><?php echo $locale->tab->users->login; ?></div>
           					<div class="column"><?php echo $locale->tab->users->name; ?></div>
           					<div class="column"><?php echo $locale->tab->users->email; ?></div>
           					<div class="column"><?php echo $locale->list->action; ?></div>
       					</li>
       					<li>
          					<div class="column"><input name="login" data-empty="no" type="text"/></div>
          					<div class="column"><input name="name" data-empty="no" type="text"/></div>
          					<div class="column"><input name="email" data-empty="no" type="email"/></div>
          					<div class="column create"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->create; ?>"></i></div>
       					</li>
<?php foreach($models['users'] as $account) { ?>
      					<li>
           					<div class="column"><input name="login" data-empty="no" type="text" value="<?php echo $account->login; ?>" disabled/></div>
          					<div class="column"><input name="name" data-empty="no" type="text" value="<?php echo $account->name; ?>"/></div>
           					<div class="column"><input name="email" data-empty="no" type="email" value="<?php echo $account->email; ?>"/></div>
               				<div class="delete"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->delete; ?>"></i></div>
               				<div class="update"><i class="fa fa-check fa-fw" title="<?php echo $locale->list->update; ?>"></i></div>
               				<div class="profile"><i class="fa fa-user fa-fw" title="<?php echo $locale->tab->users->profile; ?>"></i></div>
               				<div class="notify"><i class="fa fa-envelope fa-fw" title="<?php echo $locale->tab->users->notify; ?>"></i></div>
       					</li>
<?php } ?>
