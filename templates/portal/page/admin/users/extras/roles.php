    				<ul class="list admin-list roles" id="roles">
    					<li class="header">
             				<div class="column role"><?php echo $locale->list->fields->role; ?></div>
            				<div class="column context"><?php echo $locale->list->fields->context; ?></div>
             				<div class="column start"><?php echo $locale->list->fields->start; ?></div>
             				<div class="column end "><?php echo $locale->list->fields->end; ?></div>
             				<div class="column action add"><a class="fa fa-plus fa-fw" title="<?php echo $locale->list->actions->add; ?>"></a></div>
         				</li>
         				<li class="hidden">
             				<div class="column role">
             					<select>
<?php foreach($models['roles'] as $name) { ?>
             						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
<?php } ?>
             					</select>
             				</div>
             				<div class="column context">
             					<select>
<?php foreach($models['contexts'] as $name) { ?>
             						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
<?php } ?>
             					</select>
							</div>
             				<div class="column start"><input data-empty="no" type="date" value=""/></div>
             				<div class="column end"><input data-empty="no" type="date" value=""/></div>
             				<div class="column action remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->actions->remove; ?>"></i></div>
         				</li>
<?php foreach ((new UserHasRoleEntity())->retrieve(['where' => ['user' => $models['login']], 'many' => true]) as $entry) { ?>
<?php   if ((null !== Zord::value('context', $entry->context)) || ($entry->context == '*')) { ?>
         				<li class="data">
             				<div class="column role">
             					<select>
<?php     foreach($models['roles'] as $name) { ?>
             						<option value="<?php echo $name; ?>" <?php if ($name == $entry->role) echo 'selected'; ?>><?php echo $name; ?></option>
<?php     } ?>
             					</select>
							</div>
             				<div class="column context">
             					<select>
<?php     foreach($models['contexts'] as $name) { ?>
             						<option value="<?php echo $name; ?>" <?php if ($name == $entry->context) echo 'selected'; ?>><?php echo $name; ?></option>
<?php     } ?>
             					</select>
             				</div>
             				<div class="column start"><input data-empty="no" type="date" value="<?php echo $entry->start; ?>"/></div>
             				<div class="column end"><input data-empty="no" type="date" value="<?php echo $entry->end; ?>"/></div>
             				<div class="column action remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->actions->remove; ?>"></i></div>
         				</li>
<?php   } ?>
<?php } ?>
     				</ul>
