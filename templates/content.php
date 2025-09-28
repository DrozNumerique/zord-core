<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<?php if (!empty($base)) { ?>
	<base href="<?php echo $base; ?>">
<?php } ?>
<?php $this->render('/portal/link'); ?>
<?php $this->render('/portal/script'); ?>
</head>
<body class="content <?php echo $name; ?>">
<?php $this->render('/portal/content'); ?>
</body>
</html>
