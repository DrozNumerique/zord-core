								<select name="<?php echo $field; ?>"
<?php $this->render('#dataset', ['options' => $options]); ?>
<?php if (isset($entry)) { ?>
<?php   $this->render('#attributes', ['options' => $options]); ?>
<?php } ?>
								>
<?php     foreach ($choices as $value => $label) { ?>
             						<option value="<?php echo $value; ?>" <?php if ($value == Zord::entryValue($entry ?? null, $field, $options)) echo 'selected'; ?>><?php echo $label; ?></option>
<?php     } ?>
								</select>
