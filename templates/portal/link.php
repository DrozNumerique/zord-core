<?php if (isset($models['portal']['meta']) && is_array($models['portal']['meta'])) { ?>
	<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />
	<link rel="unapi-server" type="application/xml" title="unAPI" href="/unapi"/>
<?php } ?>
<?php if (isset($skin->favicon)) {?>
	<link rel="icon" type="image/x-icon" href="<?php echo $skin->favicon; ?>" />
<?php } ?>
<?php 
if (isset($skin->styles)) {
  foreach ($skin->styles as $style) {
    if (isset($style->href)) {
?>
	<link rel="stylesheet" type="<?php echo $style->type; ?>" media="<?php echo $style->media; ?>" href="<?php echo $style->href; ?>"/>
<?php
    } else if (isset($style->template)) {
?>
	<<style type="<?php echo $style->type; ?>" media="<?php echo $style->media; ?>">
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
	<link rel="stylesheet" type="<?php echo $style['type']; ?>" media="<?php echo $style['media']; ?>" href="<?php echo $style['href']; ?>"/>
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