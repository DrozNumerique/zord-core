var checkContext = {
	'delete': function(params) {
		return confirm(LOCALE.admin.context.delete.confirm) ? params : false;
	}
}

function getContextURLs() {
	var urls = [];
	var urlsInput = document.getElementById('urls');
	var urlsElement = document.getElementById('urlsList');
	if (urlsElement) {
		[].forEach.call(urlsElement.querySelectorAll('.data'), function(entry) {
			urls.push({
				secure:entry.children[0].firstElementChild.value == 'true' ? true : false,
				host:entry.children[1].firstElementChild.value,
				path:entry.children[2].firstElementChild.value
			}); 
		});
		urlsInput.value = JSON.stringify(urls)
	}
}

document.addEventListener("DOMContentLoaded", function(event) {
	
	var context = document.getElementById('context');
	if (context !== undefined && context !== null) {
		attachActions(context, function(entry, operation) {
			var params = {
				module:'Admin',
				action:'context',
				operation:operation,
				name:entry.parentNode.children[0].firstElementChild.value,
				title:entry.parentNode.children[1].firstElementChild.value,
				final: function() {
					if (document.body) {
						document.body.classList.remove('waiting');
					}
				}
			};
			if (params.name == undefined || params.name == null || params.name.length == 0) {
				return;
			}
			if (params.title == undefined || params.title == null || params.title.length == 0) {
				return;
			}
			if (operation in checkContext) {
				params = checkContext[operation](params);
			}
			if (params) {
				document.body.classList.add('waiting');
				invokeZord(params);
			}
		});
	}
	
	var submitData = document.getElementById('submit-data');
	if (submitData) {
		dressActions(document);
		submitData.addEventListener("click", function(event) {
			getContextURLs();
		});
	}


});