<?php     foreach (explode('|', $message) as $_message) { ?>
<?php       $__message = explode('=', $_message); ?>	
			<div class="message <?php echo $__message[0]; ?>"><?php echo $__message[1]; ?></div>
<?php     } ?>
