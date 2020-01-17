<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<?php $this->render('title'); ?>
<?php $this->render('meta'); ?>
<?php $this->render('script'); ?>
<?php $this->render('link'); ?>
</head>
<body>
<div id="main">
<?php $this->render('header'); ?>
<?php $this->render('navigation'); ?>
<?php $this->render('page'); ?>
<?php $this->render('footer'); ?>
</div>
</body>
</html>
