document.addEventListener("DOMContentLoaded", function(event) {
	
	var upload = document.getElementById('upload');
	if (upload !== undefined && upload !== null) {
		
		var folder  = document.getElementById('folder');
		var file    = document.getElementById('file');
		var replace = document.getElementById('replace');
		var preview = document.getElementById('preview');

		var uploadFile = function() {
			replace.value = "true";
			invokeZord({
				form: upload,
				success: function(result) {
					var status  = result[0]
					var message = result[1];
					var type    = result[2];
					var url     = result[3];
					alert(message);
					if (status === 'OK') {
						var path = '<a href="' + url + '">' + url + '</a>'
						if (type.startsWith('image')) {
							preview.innerHTML = path + '<img src="' + url + '" />';
						}
						if (type === 'application/pdf' || type.startsWith('application/vnd.oasis.opendocument')) {
							preview.innerHTML = path + '<iframe src="/ViewerJS/#..' + url + '" width="100%" height="1000"></iframe>';
						}
						preview.style.display = 'flex';
					}
				}
			});
		}
		
		file.addEventListener('change', function(event) {
			preview.style.display = 'none';
		});
		
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
