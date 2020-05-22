            		<div class="admin-panel-title"><?php echo $locale->tab->users->addresses; ?></div>
     				<ul class="admin-list" id="ips" data-columns="550px,60px,80px">
     					<li class="header">
             				<div class="column"><?php echo $locale->tab->users->ip; ?></div>
             				<div class="column"><?php echo $locale->tab->users->mask; ?></div>
             				<div class="column">+/-</div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
             				<div class="column">
             					<input data-empty="no" type="text" value="" class="ip"/> .
             					<input data-empty="no" type="text" value="" class="ip"/> .
             					<input data-empty="no" type="text" value="" class="ip"/> .
             					<input data-empty="no" type="text" value="" class="ip"/>
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
<?php foreach($models['ips'] as $entry) { ?>
         				<li class="data">
             				<div class="column">
<?php   $index = 0; ?>
<?php   foreach(explode('.', $entry['ip'], 4) as $number) { ?>
             					<input data-empty="no" type="text" value="<?php echo $number; ?>" class="ip"/> <?php echo $index < 3 ? '.' : ''; ?>
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
