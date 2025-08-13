document.addEventListener("DOMContentLoaded", function(event) {
	
	var name = document.getElementById('name');
	var contents = document.getElementById('contents');
	var preview = document.getElementById('preview').querySelector('div.content');
	var save = document.getElementById('save');
	var converter = new showdown.Converter({tables: true});
	var editors = document.querySelectorAll('textarea.editor');
	var dates = document.querySelectorAll('div.date');
	var items = contents.querySelectorAll('li[data-value]');
	
	function getEditor(page) {
		return document.querySelector('textarea.editor[data-page="' + page + '"]');
	}
	
	function getDate(page) {
		return document.querySelector('div.date[data-page="' + page + '"]');
	}
	
	function display(page) {
		name.value = page;
		setSessionProperty('content.name', page);
		[].forEach.call(items, function(item) {
			item.classList.remove('selected');
		});
		var item = contents.querySelector('li[data-value="' + page + '"]');
		if (item) {
			item.classList.add('selected');
			selected = contents.querySelector('div.selected');
			if (selected) {
				selected.innerHTML = item.innerHTML;
			}
		}
		var editors = document.querySelectorAll('textarea.editor');
		[].forEach.call(editors, function(editor) {
			if (editor.dataset.type == 'html') {
				editor = editor.parentNode;
			}
			editor.style.display = 'none';
		});
		[].forEach.call(dates, function(date) {
			date.style.display = 'none';
		});
		var editor = getEditor(page);
		if (editor) {
			switch(editor.dataset.type) {
				case 'md': {
					preview.classList = [];
					preview.classList.add('content');
					preview.classList.add('static');
					preview.classList.add(page);
					preview.innerHTML = converter.makeHtml(editor.value);
					preview.style.display = 'block';
					break;
				}
				case 'html': {
					preview.style.display = 'none';
					break;
				}
			}
			if (editor.dataset.type == 'html') {
				editor = editor.parentNode;
			}
			editor.style.display = 'block';
		}
		var date = getDate(page);
		if (date) {
			date.style.display = 'inline-block';
		}
	}
	
	if (preview) {
		[].forEach.call(editors, function(editor) {
			if (editor.dataset.type == 'md') {
				editor.addEventListener('input', function(event) {
					display(editor.dataset.page);
				});
			}
			if (editor.dataset.type == 'html') {
				$('textarea.editor[data-page="' + editor.dataset.page + '"]').trumbowyg({
					autogrow : true,
					lang     : LANG.substr(0, 2)
				});
			}
		});
	}
	
	if (name) {
		display(getSessionProperty('content.name', Object.keys(CONFIG.contents)[0]));
		[].forEach.call(items, function(item) {
			item.addEventListener('click', function(event) {
				display(item.dataset.value);
			});
		});
	}
	
	if (name && save) {
		save.addEventListener('click', function(event) {
			var editor = getEditor(name.value);
			if (editor) {
				invokeZord({
					module: 'Admin',
					action: 'content',
					name: name.value,
					type: editor.dataset.type,
					content: editor.value,
					success: function(result) {
						var date = getDate(name.value);
						if (date) {
							date.innerHTML = result.date;
						}
						alert(result.message);
					},
					failure: function(error) {
						alert(error.message);
					}
				});
			}
		});
	}
		
});