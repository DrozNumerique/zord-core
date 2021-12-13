						<fieldset class="keyword">
							<legend><?php echo $this->locale('admin')->tab->users->keyword; ?></legend>
    						<input type="text" value="<?php echo $models['keyword'] ?? ''; ?>"/>
<?php $this->render('#search'); ?>
						</fieldset>
						<fieldset class="order">
							<legend><?php echo $this->locale('admin')->tab->users->order; ?></legend>
<?php foreach (array_keys(Zord::value('portal', ['list','users','fields'])) as $index => $field) { ?>
							<label for="<?php echo $field; ?>"><?php echo $this->locale('portal')->list->headers->$field; ?></label>
    						<input type="radio" value="<?php echo $field; ?>" name="order" id="<?php echo $field; ?>"<?php echo $index == 0 ? ' checked' : ''; ?>/>
<?php } ?>
							<br/>
							<br/>
<?php foreach (['asc','desc'] as $index => $direction) { ?>
							<label for="<?php echo $direction; ?>"><i class="fa fa-fw fa-sort-alpha-<?php echo $direction; ?>"></i></label>
    						<input type="radio" value="<?php echo $direction; ?>" name="direction" id="<?php echo $direction; ?>"<?php echo $index == 0 ? ' checked' : ''; ?>/>
<?php } ?>
						</fieldset>
						