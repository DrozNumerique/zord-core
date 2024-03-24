<form id="upload" action="<?php echo $baseURL; ?>" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="module" value="Admin" />
	<input type="hidden" name="action" value="resource" />
	<input type="hidden" name="replace" value="false" id="replace" />
	<div>
		<label for="folder"><?php echo $locale->tab->resources->path; ?> : /<?php echo PUBLIC_RESOURCE_BASE; ?></label>
		<input type="text" name="folder" id="folder" value="/" />
		<input type="file" name="file" id="file" />
		<input type="submit" value="<?php echo $locale->tab->resources->submit; ?>" />
	</div>
</form>