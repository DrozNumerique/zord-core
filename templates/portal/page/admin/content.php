<?php $contents = Zord::value('portal', 'contents') ?? []; ?>
<input id="name" name="name" type="hidden"/>
<div id="wrapper">
    <div id="contents" class="admin-select admin-button">
     	<div class="selected"></div>
       	<ul class="list">
<?php foreach ($contents as $name) { ?>
			<li class="item" data-value="<?php echo $name; ?>"><?php echo htmlspecialchars($name); ?></li>
<?php } ?>
   	    </ul>
   	</div>
<?php foreach ($contents as $name) { ?>
<?php   $data = Zord::content($name, $lang); ?>
	<div class="date" data-page="<?php echo $name; ?>"><?php echo isset($data) ? Zord::date($data['date'], $lang) : ''; ?></div>
    <textarea class="editor" data-page="<?php echo $name; ?>"><?php echo isset($data) ? $data['content'] : ''; ?></textarea>
<?php } ?>
	<div id="save" class="admin-button"><?php echo $locale->tab->content->save; ?></div>
    <div class="content" id="preview"></div>
</div>
