function invokeZord(params) {

	var before = params.before == undefined ? null : params.before;
	var after = params.after == undefined ? null : params.after;
	var async = params.async == undefined ? true : params.async;
	var form = params.form == undefined ? null : params.form;
	var upload = params.upload == undefined ? false : params.upload;
	var uploading = params.uploading == undefined ? null : params.uploading;
	var uploaded = params.uploaded == undefined ? null : params.uploaded;
	var success = params.success == undefined ? null : params.success;
	var failure  = params.failure  == undefined ? null : params.failure;
	var target = BASEURL['zord'] + '/index.php';
	
	if (before !== null) {
		before();
	}
	
	var request = new XMLHttpRequest();
	request.open("POST", target , async);
	request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

	var query;
	if (form !== null) {
		query = new FormData(form);
	} else {
		request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		query = ['xhr=true'];
		for (var key in params) {
			if (!['before','after','success','failure','uploading','uploaded'].includes(key)) {
				query.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
			}
		}
		query = query.join("&").replace( /%20/g , "+");
	}
	
	if (upload === true) {
	    if (uploading !== null) {
	    	request.upload.onloadstart = uploading;
		}
	    if (uploaded !== null) {
	    	request.upload.onload = uploaded;
		}
	}
	
	request.onreadystatechange = function() {
		if (this.readyState === XMLHttpRequest.DONE) {
			type = this.getResponseHeader("Content-Type");
			if (this.status === 200) {
				if (type.startsWith('application/json')) {
					if (success !== null) {
						success(JSON.parse(this.responseText));
					}
				} else if (type.startsWith('text/html')) {
					if (success !== null) {
						success(this.responseText);
					} else {
						document.write(this.responseText);
						document.close();
					}
				} else if (type.startsWith('download/error')) {
					var error = JSON.parse(this.responseText);
					if (failure !== null) {
						failure(error);
					} else if (error.message !== undefined) {
						alert(error.message);
					} else {
						alert(error.code + ' ' + error.reason);
					}
				} else {
					document.body.insertAdjacentHTML('beforeend', "<iframe src='/Portal/download' style='display: none;'></iframe>");
				}
				if (after !== null) {
					after();
				}
			} else if (this.status >= 400) {
				if (type.startsWith('application/json')) {
					var error = JSON.parse(this.responseText);
					if (failure !== null) {
						failure(error);
					} else if (error.message !== undefined) {
						alert(error.message);
					} else {
						alert(error.code + ' ' + error.reason);
					}
				}
			}
		}
	};

	request.send(query);
	
}

function checkProcess(pid, offset, callback) {
	invokeZord(
		{
			module:'Process',
			action:'status',
			pid:pid,
			offset:offset,
			success:function(result) {
				if (callback !== undefined) {
					callback(result);
				}
			}
		}
	);
}

function killProcess(pid, callback) {
	invokeZord(
		{
			module:'Process',
			action:'kill',
			pid:pid,
			success:function(result) {
				if (callback !== undefined) {
					callback(result);
				}
			}
		}
	);
}

function setSessionProperties(zord) {
	sessionStorage.setItem('zord', JSON.stringify(zord));
}

function getSessionProperties() {
	zord = JSON.parse(sessionStorage.getItem('zord'));
	if (zord == undefined || zord == null) {
		zord = {};
		setSessionProperties(zord);
	}
	return zord;
}

function setSessionProperty(key, value, merge) {
	zord = getSessionProperties();
	zord = setValue(zord, key, value, merge);
	setSessionProperties(zord);
}

function getSessionProperty(key, def) {
	zord = getSessionProperties();
	value = getValue(zord, key, def);
	if ((value == undefined || value == null) && (def !== undefined && def !== null)) {
		value = def;
		setSessionProperty(key, value);
	}
	return value;
}

function setContextProperty(key, value, merge) {
	setSessionProperty(CONTEXT + '.' + key, value, merge);
}

function getContextProperty(key, def) {
	return getSessionProperty(CONTEXT + '.' + key, def);
}

function setValue(object, key, value, merge) {
	if (merge == undefined || merge == null || !merge) {
		object = setValue(object, key, null, true);
	}
	keys = key.split('.');
	temp = null;
	for (index = keys.length ; index > 0 ; index--) {
		temp = {};
		temp[keys[index - 1]] = value;
		value = temp;
	}
	$.extend(true, object, temp);
	return object;
}

function getValue(object, key) {
	keys = key.split('.');
	value = object;
	for (index = 0 ; index < keys.length ; index++) {
		value = value[keys[index]];
		if (value == undefined || value == null) {
			break;
		}
	}
	return value;
}
