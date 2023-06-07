<html lang="<?php echo $lang; ?>">
<head>
	<base href="<?php echo $base; ?>">
<?php $this->render('title'); ?>
<?php $this->render('meta'); ?>
<?php $this->render('script'); ?>
<?php $this->render('link'); ?>
</head>
<body class="<?php echo $context; ?>">
<div id="main">
<?php $this->render('header'); ?>
<?php $this->render('navigation'); ?>
<?php $this->render('page'); ?>
<?php $this->render('footer'); ?>
</div>
</body>
</html>
