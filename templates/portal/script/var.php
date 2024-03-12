		var BASEURL = <?php echo Zord::arrayToJS($portal['baseURL']); ?>;
		var CONTEXT = '<?php echo $context; ?>';
		var LANG    = '<?php echo $lang; ?>';
		var USER    = <?php echo Zord::arrayToJS($portal['user']); ?>;	
		var HASH    = <?php echo Zord::arrayToJS(Zord::getConfig('hash')); ?>;
<?php if (isset($lastURL)) { ?>
		var LASTURL = '<?php echo $lastURL;?>';
<?php } ?>
