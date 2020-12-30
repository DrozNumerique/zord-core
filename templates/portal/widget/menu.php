		<div id="navcontent">
			<ul>
<?php foreach ($models as $entry) { ?>
				<li id="<?php echo 'menu_'.$entry['name']; ?>" class="<?php echo (isset($entry['class']) && !empty($entry['class'])) ? implode(' ', $entry['class']) : ''; ?>">
<?php   $this->render($entry['render'], ['entry' => $entry]); ?>
				</li>
<?php } ?>
			</ul>
		</div>
