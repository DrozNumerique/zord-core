            		<div class="admin-panel-title"><?php echo $locale->tab->users->ipv4; ?></div>
     				<ul class="admin-list" id="ipv4" data-columns="550px,60px,80px">
     					<li class="header">
             				<div class="column"><?php echo $locale->tab->users->ip; ?></div>
             				<div class="column"><?php echo $locale->tab->users->mask; ?></div>
             				<div class="column">+/-</div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
             				<div class="column">
             					<input data-empty="no" type="text" value="" class="ipv4"/> .
             					<input data-empty="no" type="text" value="" class="ipv4"/> .
             					<input data-empty="no" type="text" value="" class="ipv4"/> .
             					<input data-empty="no" type="text" value="" class="ipv4"/>
             				</div>
             				<div class="column"><input data-empty="no" type="number" value="32" min="0" max="32"/></div>
             				<div class="column">
             					<select>
             						<option value="1"><?php echo $locale->tab->users->include; ?></option>
             						<option value="0"><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php foreach($models['ipv4'] as $entry) { ?>
         				<li class="data">
             				<div class="column">
<?php   $index = 0; ?>
<?php   foreach(explode('.', $entry['ip'], 4) as $number) { ?>
             					<input data-empty="no" type="text" value="<?php echo $number; ?>" class="ipv4"/> <?php echo $index < 3 ? '.' : ''; ?>
<?php     $index++; ?>
<?php   } ?>
             				</div>
             				<div class="column"><input data-empty="no" type="number" value="<?php echo $entry['mask']; ?>" min="0" max="32"/></div>
             				<div class="column">
             					<select>
             						<option value="1" <?php if ($entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->include; ?></option>
             						<option value="0" <?php if (!$entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php } ?>
     				</ul>
            		<div class="admin-panel-title"><?php echo $locale->tab->users->ipv6; ?></div>
     				<ul class="admin-list" id="ipv6" data-columns="550px,60px,80px">
     					<li class="header">
             				<div class="column"><?php echo $locale->tab->users->ip; ?></div>
             				<div class="column"><?php echo $locale->tab->users->mask; ?></div>
             				<div class="column">+/-</div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
             				<div class="column">
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6"/>
             				</div>
             				<div class="column"><input data-empty="no" type="number" value="128" min="0" max="128"/></div>
             				<div class="column">
             					<select>
             						<option value="1"><?php echo $locale->tab->users->include; ?></option>
             						<option value="0"><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php foreach($models['ipv6'] as $entry) { ?>
         				<li class="data">
             				<div class="column">
<?php   $index = 0; ?>
<?php   foreach(explode(':', $entry['ip'], 8) as $number) { ?>
             					<input data-empty="no" type="text" value="<?php echo $number; ?>" class="ipv6" maxlength="4"/> <?php echo $index < 7 ? ':' : ''; ?>
<?php     $index++; ?>
<?php   } ?>
             				</div>
             				<div class="column"><input data-empty="no" type="number" value="<?php echo $entry['mask']; ?>" min="0" max="128"/></div>
             				<div class="column">
             					<select>
             						<option value="1" <?php if ($entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->include; ?></option>
             						<option value="0" <?php if (!$entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
<?php } ?>
     				</ul>
     				