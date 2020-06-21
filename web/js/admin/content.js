document.addEventListener("DOMContentLoaded", function(event) {
	
	var name = document.getElementById('name');
	var contents = document.getElementById('contents');
	var preview = document.getElementById('preview');
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
		[].forEach.call(items, function(item) {
			item.classList.remove('selected');
		});
		item = contents.querySelector('li[data-value="' + page + '"]');
		if (item) {
			item.classList.add('selected');
			selected = contents.querySelector('div.selected');
			if (selected) {
				selected.innerHTML = item.dataset.value;
			}
		}
		[].forEach.call(editors, function(editor) {
			editor.style.display = 'none';
		});
		[].forEach.call(dates, function(date) {
			date.style.display = 'none';
		});
		editor = getEditor(page);
		if (editor) {
			editor.style.display = 'block';
			preview.innerHTML = converter.makeHtml(editor.value);
		}
		date = getDate(page);
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
		display('home');
		[].forEach.call(items, function(item) {
			item.addEventListener('click', function(event) {
				display(item.dataset.value);
			});
		});
	}
	
	if (name && save) {
		save.addEventListener('click', function(event) {
			editor = getEditor(name.value);
			if (editor) {
				invokeZord({
					module: 'Admin',
					action: 'content',
					name: name.value,
					content: editor.value,
					success: function(result) {
						date = getDate(name.value);
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