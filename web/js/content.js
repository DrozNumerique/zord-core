var dressContent = function(root) {
	[].forEach.call(root.querySelectorAll('.content.editable[data-name]'), function (content) {
		var converter = new showdown.Converter();
		var display = content.querySelector(".display");
		var editor  = content.querySelector(".editor");
		var text    = content.querySelector(".text");
		var save    = content.querySelector(".save");
		var cancel  = content.querySelector(".cancel");
		var _display = display.style.display;
		if (display && editor && text) {
			display.addEventListener('click', function(event) {
				if (event.ctrlKey || event.metaKey) {
					editor.style.height = display.offsetHeight + 'px';
					display.style.display = 'none';
					editor.style.display = 'block';
				}
			});
		}
		if (cancel && display && editor) {
			cancel.addEventListener('click', function(event) {
				display.style.display = _display;
				editor.style.display = 'none';
			});
		}
		if (text && editor && save && display && converter) {
			save.addEventListener('click', function(event) {
				invokeZord({
					module: save.dataset.module,
					action: save.dataset.action,
					name: content.dataset.name,
					locale: content.dataset.lang,
					content: text.value,
					success: function(result) {
						display.innerHTML = text.value !== '' ? converter.makeHtml(text.value) : result.holder;
						if (text.value !== '') {
							display.classList.remove('empty');
						} else {
							display.classList.add('empty');
						}
						alert(result.message);
					},
					failure: function(error) {
						alert(error.message);
					},
					after: function() {
						display.style.display = _display;
						editor.style.display = 'none';
					}
				});
			});
		}
	});	
};

document.addEventListener("DOMContentLoaded", function(event) {
	
	dressContent(document);
	
});
