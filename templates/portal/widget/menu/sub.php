					<div class="sub"><?php echo $entry->label; ?>
						<ul>
<?php     foreach ($entry->menu as $menu) { ?>
							<li id="<?php echo 'menu_'.$entry->name.'_'.$menu->name; ?>" class="<?php echo (isset($menu->class) && !empty($menu->class)) ? implode(' ', $menu->class) : ''; ?>">
<?php       $this->render('#'.$menu->render, ['entry' => $menu]);?>
							</li>
<?php     } ?>
						</ul>
					</div>
