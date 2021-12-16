								<input name="<?php echo $field; ?>"
								       type="<?php echo $type ?>"
								       value="<?php echo Zord::entryValue($entry ?? null, $field, $options); ?>"
<?php $this->render('#dataset', ['options' => $options]); ?>
<?php $this->render('#attributes', ['options' => $options, 'entry' => $entry ?? null]); ?>
								/>
