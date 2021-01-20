		var BASEURL = <?php echo Zord::arrayToJS($models['portal']['baseURL'] ?? []); ?>;
		var CONTEXT = '<?php echo $context; ?>';
		var LANG    = '<?php echo $lang; ?>';
		var USER    = <?php echo Zord::arrayToJS($models['portal']['user'] ?? []); ?>;	
		var HASH    = <?php echo Zord::arrayToJS(Zord::getConfig('hash') ?? []); ?>;
