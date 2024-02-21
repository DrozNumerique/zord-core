<?php     foreach (Zord::messages($message) as $type => $messages) { ?>
<?php       foreach ($messages as $message) { ?>
				<div class="message <?php echo $type; ?>"><?php echo $message; ?></div>
<?php       } ?>
<?php     } ?>
