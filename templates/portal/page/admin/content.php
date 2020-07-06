<?php $contents = $admin->contentList(); ?>
<input id="name" name="name" type="hidden"/>
<div id="wrapper">
    <div id="contents" class="admin-select admin-button">
     	<div class="selected"></div>
       	<ul class="list">
<?php foreach ($contents as $name) { ?>
			<li class="item" data-value="<?php echo $name; ?>"><?php echo htmlspecialchars($admin->contentLabel($name)); ?></li>
<?php } ?>
   	    </ul>
   	</div>
<?php foreach ($contents as $name) { ?>
<?php   $content = Zord::content($name, $lang); ?>
	<div class="date" data-page="<?php echo $name; ?>"><?php echo isset($content) ? Zord::date(date('YmdHis', filemtime($content)), $lang) : ''; ?></div>
    <textarea class="editor" data-page="<?php echo $name; ?>"><?php echo isset($content) ? file_get_contents($content) : ''; ?></textarea>
<?php } ?>
<?php Zord::log($locale); ?>
	<div id="save" class="admin-button"><?php echo $locale->save; ?></div>
	<div id="preview">
	    <div class="content"></div>
	</div>
</div>
