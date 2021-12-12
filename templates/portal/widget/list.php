					<ul class="list <?php echo $id; ?><?php echo Zord::classList($class ?? null); ?>" id="<?php echo $id; ?>">
						<li class="header">
<?php foreach (array_keys($fields) as $field) { ?>
							<div class="column <?php echo $field; ?><?php echo Zord::classList($options[$field]['header']['class'] ?? null); ?>">
<?php   $this->render('headers/'.($headers[$field] ?? 'label'), ['field' => $field, 'options' => $options[$field] ?? []]); ?>
							</div>
<?php } ?>
<?php if (!empty($actions)) { ?>
							<div class="column actions<?php echo Zord::classList($options['actions']['header']['class'] ?? null); ?>">
<?php   $this->render('headers/'.($headers['actions'] ?? 'label'), ['field' => 'actions', 'options' => $options['actions'] ?? []]); ?>
							</div>
<?php } ?>
						</li>
<?php if ($create ?? false) { ?>
						<li class="<?php echo $create ?>">
<?php   foreach ($fields as $field => $type) { ?>
							<div class="column <?php echo $field; ?><?php echo Zord::classList($options[$field]['class'] ?? null); ?>">
<?php	 $this->render('fields/'.(in_array($type, Zord::value('portal', ['list','input'])) ? 'input' : $type), ['field' => $field, 'type' => $type, 'options' => $options[$field] ?? [], 'choices' => $choices[$field] ?? []]); ?>
							</div>
<?php   } ?>
<?php   if (!empty($actions)) { ?>
							<div class="column action create">
								<i class="fa fa-plus fa-fw" title="<?php echo $this->locale('portal')->list->actions->create; ?>"></i>
							</div>
<?php   } ?>
						</li>
<?php } ?>
<?php foreach($data as $entry) { ?>
	  					<li class="data">
<?php   foreach ($fields as $field => $type) { ?>
							<div class="column <?php echo $field; ?><?php echo Zord::classList($options[$field]['class'] ?? null); ?>">
<?php     $this->render('fields/'.(in_array($type, Zord::value('portal', ['list','input'])) ? 'input' : $type), ['field' => $field, 'type' => $type, 'entry' => $entry, 'options' => $options[$field] ?? [], 'choices' => $choices[$field] ?? []]); ?>
							</div>
<?php   } ?>
<?php   foreach ($actions ?? [] as $action => $icon) { ?>
							<div class="action <?php echo $action; ?>">
								<i class="fa fa-<?php echo $icon; ?> fa-fw" title="<?php echo $this->locale('portal')->list->actions->$action; ?>"></i>
							</div>
<?php   } ?>
						</li>
<?php } ?>
					</ul>