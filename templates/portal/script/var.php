		var BASEURL = '<?php echo $baseURL; ?>';
		var CONTEXT = '<?php echo $context; ?>';
		var LANG = '<?php echo $lang; ?>';
		var USER = {
			login: '<?php echo $user->login; ?>',
			name: '<?php echo str_replace("'", "\\'", $user->name); ?>',
			email: '<?php echo $user->email; ?>',
			session: <?php echo $user->session ? "'".$user->session."'\n" : "undefined\n"; ?>
		};		
