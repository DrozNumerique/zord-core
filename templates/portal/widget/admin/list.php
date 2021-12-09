   					<ul class="admin-list" id="<?php echo $id; ?>" data-columns="<?php echo $widths; ?>">
   						<li class="header">
<?php foreach (array_keys($columns) as $field) { ?>
           					<div class="column <?php echo $field; ?>"><?php echo $this->locale('admin')->list->$field ?? $this->locale('admin')->tab->$tab->$field; ?></div>
<?php } ?>
<?php if (!empty($actions)) { ?>
           					<div class="column actions"><a class="fa fa-cog fa-fw" title="<?php echo $this->locale('admin')->list->actions; ?>"></a></div>
<?php } ?>
       					</li>
<?php if ($create ?? false) { ?>
       					<li class="<?php echo $create ?>">
<?php   foreach ($columns as $field => $type) { ?>
          					<div class="column <?php echo $field; ?>"><input name="<?php echo $field; ?>" data-empty="no" type="<?php echo $type; ?>"/></div>
<?php   } ?>
          					<div class="column create"><i class="fa fa-plus fa-fw" title="<?php echo $this->locale('admin')->list->create; ?>"></i></div>
       					</li>
<?php } ?>
<?php foreach($data as $entry) { ?>
      					<li>
<?php   foreach ($columns as $field => $type) { ?>
           					<div class="column <?php echo $field; ?>"><input name="<?php echo $field; ?>" data-empty="no" type="<?php echo $type; ?>" value="<?php echo is_object($entry) ? $entry->$field : $entry[$field]; ?>"<?php echo in_array($field, $disabled ?? []) ? ' disabled' : ''; ?>/></div>
<?php   } ?>
<?php   foreach ($actions as $action => $icon) { ?>
               				<div class="<?php echo $action; ?>"><i class="fa fa-<?php echo $icon; ?> fa-fw" title="<?php echo $this->locale('admin')->list->$action ?? $this->locale('admin')->tab->$tab->$action; ?>"></i></div>
<?php   } ?>
       					</li>
<?php } ?>
