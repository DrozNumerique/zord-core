<?php   $index = 0; ?>
<?php   foreach(explode(':', Zord::entryValue($entry ?? null, $field, $options), 8) as $number) { ?>
             					<input type="text" value="<?php echo $number; ?>" class="ipv6" maxlength="4"
<?php $this->render('#dataset', ['options' => $options]); ?>
<?php if (isset($entry)) { ?>
<?php   $this->render('#attributes', ['options' => $options]); ?>
<?php } ?>
             					/> <?php echo ($index < 7 ? ':' : '')."\n"; ?>
<?php     $index++; ?>
<?php   } ?>
 