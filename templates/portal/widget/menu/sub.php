					<div class="sub"><?php echo in_array(substr($entry['label'], 0, 1), ['/','#']) ? $this->render($entry['label']) : $entry['label']."\n"; ?>
						<ul>
<?php     foreach ($entry['menu'] as $menu) { ?>
							<li id="<?php echo 'menu_'.$entry['name'].'_'.$menu['name']; ?>" class="<?php echo (isset($menu['class']) && !empty($menu['class'])) ? implode(' ', $menu['class']) : ''; ?>">
<?php       $this->render('#'.$menu['render'], ['entry' => $menu]);?>
							</li>
<?php     } ?>
						</ul>
					</div>
