<?php if (isset($skin->favicon)) {?>
	<link rel="icon" type="<?php echo $skin->favicon->type; ?>" href="<?php echo $skin->favicon->href.Zord::clientCacheQuery(); ?>" />
<?php } ?>
<?php 
if (isset($skin->styles)) {
  foreach ($skin->styles as $style) {
    if (isset($style->href)) {
?>
	<link rel="stylesheet" type="<?php echo $style->type; ?>" media="<?php echo $style->media; ?>" href="<?php echo $style->href.Zord::clientCacheQuery(); ?>"/>
<?php
    } else if (isset($style->template)) {
?>
	<style type="<?php echo $style->type; ?>" media="<?php echo $style->media; ?>">
<!--
<?php
        $this->render($style->template);
?>
-->
    </style>
<?php
    }
  }
}
?>
<?php 
if (isset($models['portal']['styles'])) {
  foreach ($models['portal']['styles'] as $style) {
    if (isset($style['href'])) {
?>
	<link rel="stylesheet" type="<?php echo $style['type']; ?>" media="<?php echo $style['media']; ?>" href="<?php echo $style['href'].Zord::clientCacheQuery(); ?>"/>
<?php
    } else if (isset($style['template'])) {
?>
	<style type="<?php echo $style['type']; ?>" media="<?php echo $style['media']; ?>">
<!--
<?php
        $this->render($style['template']);
?>
-->
    </style>
<?php
    }
  }
}
?>