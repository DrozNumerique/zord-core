<?php   $index = 0; ?>
<?php   foreach(explode('.', Zord::entryValue($entry ?? null, $field, $options), 4) as $number) { ?>
             					<input type="text" value="<?php echo $number; ?>" class="ipv4"
<?php     $this->render('#dataset', ['options' => $options]); ?>
<?php     $this->render('#attributes', ['options' => $options, 'entry' => $entry ?? null]); ?>
             					/> <?php echo ($index < 3 ? '.' : '')."\n"; ?>
<?php     $index++; ?>
<?php   } ?>
