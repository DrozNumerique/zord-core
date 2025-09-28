<html lang="<?php echo $lang; ?>">
<head>
<?php if (!empty($base)) { ?>
	<base href="<?php echo $base; ?>">
<?php } ?>
<?php $this->render('title'); ?>
<?php $this->render('meta'); ?>
<?php $this->render('script'); ?>
<?php $this->render('link'); ?>
</head>
<body class="<?php echo $context; ?> <?php echo $device; ?>">
<div id="main">
<?php $this->render('header'); ?>
<?php $this->render('navigation'); ?>
<?php $this->render('page'); ?>
<?php $this->render('footer'); ?>
</div>
</body>
</html>
