     				<ul class="list admin ip ipv6" id="ipv6">
     					<li class="header">
             				<div class="column ip"><?php echo $locale->list->headers->ip; ?></div>
             				<div class="column mask"><?php echo $locale->list->headers->mask; ?></div>
             				<div class="column include">+/-</div>
             				<div class="column action add"><a class="fa fa-plus fa-fw" title="<?php echo $locale->list->actions->add; ?>"></a></div>
         				</li>
         				<li class="hidden">
             				<div class="column ip">
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6" maxlength="4"/> :
             					<input data-empty="no" type="text" value="" class="ipv6"/>
             				</div>
             				<div class="column mask"><input data-empty="no" type="number" value="128" min="0" max="128"/></div>
             				<div class="column include">
             					<select>
             						<option value="1"><?php echo $locale->tab->users->include; ?></option>
             						<option value="0"><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="column action remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->actions->remove; ?>"></i></div>
         				</li>
<?php foreach($models['ipv6'] as $entry) { ?>
         				<li class="data">
             				<div class="column ip">
<?php   $index = 0; ?>
<?php   foreach(explode(':', $entry['ip'], 8) as $number) { ?>
             					<input data-empty="no" type="text" value="<?php echo $number; ?>" class="ipv6" maxlength="4"/> <?php echo $index < 7 ? ':' : ''; ?>
<?php     $index++; ?>
<?php   } ?>
             				</div>
             				<div class="column mask"><input data-empty="no" type="number" value="<?php echo $entry['mask']; ?>" min="0" max="128"/></div>
             				<div class="column include">
             					<select>
             						<option value="1" <?php if ($entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->include; ?></option>
             						<option value="0" <?php if (!$entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="column action remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->actions->remove; ?>"></i></div>
         				</li>
<?php } ?>
     				</ul>
     				