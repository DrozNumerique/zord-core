document.addEventListener("DOMContentLoaded", function(event) {
	
	var upload = document.getElementById('upload');
	if (upload !== undefined && upload !== null) {
		
		var folder  = document.getElementById('folder');
		var file    = document.getElementById('file');
		var replace = document.getElementById('replace');

		var uploadFile = function() {
			replace.value = "true";
			invokeZord({
				form: upload,
				success: function(result) {
					var message = result[1];
					alert(message);
				}
			});
		}
		
		upload.addEventListener('submit', function(event) {
			replace.value = "false";
			event.preventDefault();
			if (file.files.length === 0) {
				return;
			}
			invokeZord({
				module  : 'Admin',
				action  : 'resource',
				folder  : folder.value,
				filename: file.files[0].name,
				success : function(result) {
					var status  = result[0]
					var message = result[1];
					var really  = result[2];
					if (status === 'OK') {
						uploadFile()
					} else {
						if (really === true) {
							if (confirm(message)) {
								uploadFile();
							}
						} else {
							alert(message);
						}
					}
				}
			});
		});
	}
	
});
