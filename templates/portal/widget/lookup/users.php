						<fieldset class="keyword">
							<legend><?php echo $this->locale('admin')->tab->users->keyword; ?></legend>
    						<input class="search" type="text" value="<?php echo $models['keyword'] ?? ''; ?>"/>
<?php $this->render('#search'); ?>
						</fieldset>
						<input name="order" type="hidden" value="<?php echo $models['order'] ?? 'login'; ?>"/>
						<input name="direction" type="hidden" value="<?php echo $models['direction'] ?? 'asc'; ?>"/> 
						