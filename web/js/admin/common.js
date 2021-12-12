document.addEventListener("DOMContentLoaded", function(event) {
	
	[].forEach.call(document.querySelectorAll('.admin-menu-entry'), function(entry) {
		entry.addEventListener("click", function(event) {
			invokeZord({
				module:'Admin',
				action:'index',
				tab:entry.getAttribute('data-tab')
			});
		});
	});
	
});
