<?php     foreach (explode('|', $message) as $_message) { ?>
<?php       $__message = explode('=', $_message); ?>	
<?php       foreach (explode('§', $__message[1]) as $___message) { ?>
			<div class="message <?php echo $__message[0]; ?>"><?php echo $___message; ?></div>
<?php       } ?>
<?php     } ?>
