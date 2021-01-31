var invokeZord = function(params) {

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

var checkProcess = function(pid, offset, callback) {
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

var killProcess = function(pid, callback) {
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

var setSessionProperties = function(zord) {
	sessionStorage.setItem('zord', JSON.stringify(zord));
}

var getSessionProperties = function() {
	zord = JSON.parse(sessionStorage.getItem('zord'));
	if (zord == undefined || zord == null) {
		zord = {};
		setSessionProperties(zord);
	}
	return zord;
}

var setValue = function(object, key, value, merge) {
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

var getValue = function(object, key) {
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

var setSessionProperty = function(key, value, merge) {
	zord = getSessionProperties();
	zord = setValue(zord, key, value, merge);
	setSessionProperties(zord);
}

var getSessionProperty = function(key, def) {
	zord = getSessionProperties();
	value = getValue(zord, key, def);
	if ((value == undefined || value == null) && (def !== undefined && def !== null)) {
		value = def;
		setSessionProperty(key, value);
	}
	return value;
}

var setPortalProperty = function(key, value, merge) {
	setSessionProperty('portal.' + key, value, merge);
}

var getPortalProperty = function(key, def) {
	return getSessionProperty('portal.' + key, def);
}

var setContextProperty = function(key, value, merge) {
	setSessionProperty('context.' + CONTEXT + '.' + key, value, merge);
}

var getContextProperty = function(key, def) {
	return getSessionProperty('context.' + CONTEXT + '.' + key, def);
}

var setUserProperty = function(key, value, merge) {
	setSessionProperty('user.' + USER.login + '.' + key, value, merge);
}

var getUserProperty = function(key, def) {
	return getSessionProperty('user.' + USER.login + '.' + key, def);
}

var loadData = function(params) {
	var action = params.action;
	var keys = getSessionProperty('data.keys', {});
	var json = JSON.stringify(params);
	if (keys[json] !== undefined) {
		_loadData(Object.assign(params, {action:action}), keys[json]);
	} else {
		invokeZord(Object.assign(params, {
			action  : 'hashKey',
			_action : action,
			success : function(key) {
				keys[json] = key;
				setSessionProperty('data.keys', keys);
				_loadData(Object.assign(params, {action:action}), key);
			}
		}));
	}
}

var _loadData = function(params, key) {
	data = getSessionProperty(key, null);
	hash = getSessionProperty('data.hash', {});			
	if (data == null || hash[key] == undefined || HASH[key] == undefined || hash[key] !== HASH[key]) {
		invokeZord(Object.assign(params, {
			before  : function() {
				if (params.wait ?? false) {
					$dialog.wait();
				}
			},
			after   : function() {
				if (params.wait ?? false) {
					$dialog.hide();
				}
			},
			success : function(data) {
				setSessionProperty(key, data);
				setSessionProperty('data.hash', HASH);
			}
		}));
	}
}

var getOptionValue = function(value) {
	return value.startsWith('key:') ? value.substr('key:'.length) : value;
}
