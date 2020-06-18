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
		return confirm(PORTAL.locales[LANG].admin.context.delete.confirm);
	}
	return true;
}

function getData() {
	var urls = [];
	var context = document.getElementById('context').value;
	var urlsElement = document.getElementById('urls');
	if (urlsElement) {
		[].forEach.call(urlsElement.querySelectorAll('.data'), function(entry) {
			urls.push({
				secure:entry.children[0].firstElementChild.value == 'true' ? true : false,
				host:entry.children[1].firstElementChild.value,
				path:entry.children[2].firstElementChild.value
			}); 
		});
	}
	var data = {
		context:context,
		urls:JSON.stringify(urls)
	};
	return data;
}

document.addEventListener("DOMContentLoaded", function(event) {
	
	attach(['urls'], function(entry, operation) {
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
	
	var submitURLs = document.getElementById('submit-urls');
	if (submitURLs != undefined) {
		submitURLs.addEventListener("click", function(event) {
			var data = getData();
			invokeZord({
				module:'Admin',
				action:'urls',
				ctx:data.context,
				urls:data.urls
			});
		});
	}

});