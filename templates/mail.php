<!DOCTYPE html>
<html>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    	<title><?php echo $models['mail']['subject']; ?></title>
		<base href="<?php echo $scheme.'://'.$host; ?>">
<?php if (isset($models['mail']['styles'])) { ?>
<?php   foreach ($models['mail']['styles'] as $style) { ?>
<?php   $css = Zord::getComponentPath('/web/css/'.$style.'.css'); ?>
<?php     if (file_exists($css)) { ?>
		<style>
<?php       include $css; ?>
        </style>
<?php     } ?>
<?php   } ?>
<?php } ?>
    </head>
    <body>
<?php $this->render('body'); ?>
    </body>
</html>
