					<div id="others">
<?php if (isset($models['others'])) { ?>
<?php   foreach ($models['others'] as $other) { ?>
						<div class="admin-panel-warning"><?php echo $other[0].' '.$locale->tab->users->match.' '.$other[1]; ?></div>
<?php   } ?>
<?php } ?>
					</div>