document.addEventListener("DOMContentLoaded", function(event) {
	
	var name = document.getElementById('name');
	var contents = document.getElementById('contents');
	var preview = document.getElementById('preview').querySelector('div.content');
	var save = document.getElementById('save');
	var converter = new showdown.Converter();
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
		[].forEach.call(editors, function(editor) {
			editor.style.display = 'none';
		});
		[].forEach.call(dates, function(date) {
			date.style.display = 'none';
		});
		var editor = getEditor(page);
		if (editor) {
			editor.style.display = 'block';
			preview.classList = [];
			preview.classList.add('content');
			preview.classList.add(page);
			preview.innerHTML = converter.makeHtml(editor.value);
		}
		var date = getDate(page);
		if (date) {
			date.style.display = 'inline-block';
		}
	}
	
	if (preview) {
		[].forEach.call(editors, function(editor) {
			editor.addEventListener('input', function(event) {
				display(editor.dataset.page);
			});
		});
	}
	
	if (name) {
		display(getSessionProperty('content.name', CONFIG.contents[0]));
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