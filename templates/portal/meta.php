	<meta charset="UTF-8" />
	<meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="0" />
<?php
if (isset($models['portal']['meta']) && is_array($models['portal']['meta'])) {
    foreach ($models['portal']['meta'] as $data) {
?>
	<meta name="<?php echo $data['name']; ?>" content="<?php echo $data['content']; ?>" <?php if (isset($data['scheme'])) { ?>scheme="<?php echo $data['scheme']; ?>" <?php } ?><?php if (isset($data['lang'])) { ?>xml:lang="<?php echo $data['lang']; ?>" <?php } ?>/>   
<?php
  }
}
?>