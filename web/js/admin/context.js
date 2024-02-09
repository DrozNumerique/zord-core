function checkContext(operation, data) {
	if (data == undefined || data == null) {
		return false;
	}
	if (data.name == undefined || data.name == null || data.name.length == 0) {
		return false;
	}
	if (data.title == undefined || data.title == null || data.title.length == 0) {
		return false;
	}
	if (operation == 'delete') {
		return confirm(LOCALE.admin.context.delete.confirm);
	}
	return true;
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
			var data = {
				name:entry.parentNode.children[0].firstElementChild.value,
				title:entry.parentNode.children[1].firstElementChild.value
			};
			if (checkContext(operation, data)) {
				invokeZord({
					module:'Admin',
					action:'context',
					operation:operation,
					name:data.name,
					title:data.title
				});
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