				<div align="center">
<?php if (isset($models['error'])) { ?>
           			<div class="admin-panel-warning"><?php echo $models['error']; ?></div>
           			<div class="admin-panel-warning"><?php echo $locale->tab->users->mail->to.' : '.$models['account']; ?></div>
           			<div class="admin-panel-warning"><a href="<?php echo $models['activation']; ?>"><?php echo $locale->tab->users->mail->activation; ?></a></div>
           			<br/>
           			<br/>
<?php } ?>
   					<ul class="admin-list" id="account" data-columns="210px,210px,210px">
   						<li class="header">
           					<div class="column"><?php echo $locale->tab->users->login; ?></div>
           					<div class="column"><?php echo $locale->tab->users->name; ?></div>
           					<div class="column"><?php echo $locale->tab->users->email; ?></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
       					</li>
       					<li>
          					<div class="column"><input name="login" data-empty="no" type="text"/></div>
          					<div class="column"><input name="name" data-empty="no" type="text"/></div>
          					<div class="column"><input name="email" data-empty="no" type="email"/></div>
          					<div class="create"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->create; ?>"></i></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
       					</li>
<?php foreach((new UserEntity())->retrieve($models['criteria'] ?? null) as $account) { ?>
      					<li>
           					<div class="column"><input name="login" data-empty="no" type="text" value="<?php echo $account->login; ?>" disabled/></div>
          					<div class="column"><input name="name" data-empty="no" type="text" value="<?php echo $account->name; ?>"/></div>
           					<div class="column"><input name="email" data-empty="no" type="email" value="<?php echo $account->email; ?>"/></div>
           					<div class="delete"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->delete; ?>"></i></div>
           					<div class="update"><i class="fa fa-check fa-fw" title="<?php echo $locale->list->update; ?>"></i></div>
           					<div class="profile"><i class="fa fa-user fa-fw" title="<?php echo $locale->tab->users->profile; ?>"></i></div>
       					</li>
<?php } ?>
   					</ul>
<?php if ($models['count'] > $models['limit']) { ?>
                    <ul id="pagination" data-count="<?php echo $models['count']; ?>" data-limit="<?php echo $models['limit']; ?>" data-offset="<?php echo $models['offset']; ?>">
                    	<li class="cursor previous">
                    		<span><?php echo $locale->tab->users->previous; ?></span>
                    	</li>
                    	<li class="index">
                    		<select>
<?php   for ($offset = 0 ; $offset < $models['count'] ; $offset += $models['limit']) { ?>
								<option value="<?php echo $offset; ?>"<?php echo $offset == $models['offset'] ? ' selected' : ''; ?>><?php echo $offset + 1; ?> - <?php echo min([$offset + $models['limit'], $models['count']]); ?></option>
<?php   } ?>
							</select>
						</li>
                    	<li class="cursor next">
                    		<span><?php echo $locale->tab->users->next; ?></span>
                    	</li>
                    </ul>
<?php } ?>
				</div>
