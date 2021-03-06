           			<div class="admin-panel-title"><?php echo $locale->tab->users->roles; ?></div>
    				<ul class="admin-list" id="roles" data-columns="170px,170px,170px,170px">
    					<li class="header">
             				<div class="column"><?php echo $locale->tab->users->role; ?></div>
            				<div class="column"><?php echo $locale->tab->users->context; ?></div>
             				<div class="column"><?php echo $locale->tab->users->start; ?></div>
             				<div class="column"><?php echo $locale->tab->users->end; ?></div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
             				<div class="column">
             					<select>
<?php foreach($models['roles'] as $name) { ?>
             						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
<?php } ?>
             					</select>
             				</div>
             				<div class="column">
             					<select>
<?php foreach($models['contexts'] as $name) { ?>
             						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
<?php } ?>
             					</select>
							</div>
             				<div class="column"><input data-empty="no" type="date" value=""/></div>
             				<div class="column"><input data-empty="no" type="date" value=""/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php foreach ((new UserHasRoleEntity())->retrieve(['where' => ['user' => $models['login']], 'many' => true]) as $entry) { ?>
<?php   if ((null !== Zord::value('context', $entry->context)) || ($entry->context == '*')) { ?>
         				<li class="data">
             				<div class="column">
             					<select>
<?php     foreach($models['roles'] as $name) { ?>
             						<option value="<?php echo $name; ?>" <?php if ($name == $entry->role) echo 'selected'; ?>><?php echo $name; ?></option>
<?php     } ?>
             					</select>
							</div>
             				<div class="column">
             					<select>
<?php     foreach($models['contexts'] as $name) { ?>
             						<option value="<?php echo $name; ?>" <?php if ($name == $entry->context) echo 'selected'; ?>><?php echo $name; ?></option>
<?php     } ?>
             					</select>
             				</div>
             				<div class="column"><input data-empty="no" type="date" value="<?php echo $entry->start; ?>"/></div>
             				<div class="column"><input data-empty="no" type="date" value="<?php echo $entry->end; ?>"/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php   } ?>
<?php } ?>
     				</ul>
