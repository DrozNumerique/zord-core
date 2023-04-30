 var invokeZord = function(params) {

	var before = params.before == undefined ? null : params.before;
	var after = params.after == undefined ? null : params.after;
	var async = params.async == undefined ? true : params.async;
	var form = params.form == undefined ? null : params.form;
	var upload = params.upload == undefined ? false : params.upload;
	var uploading = params.uploading == undefined ? null : params.uploading;
	var uploaded = params.uploaded == undefined ? null : params.uploaded;
	var success = params.success == undefined ? null : params.success;
	var failure  = params.failure == undefined ? null : params.failure;
	var inner  = params.inner == undefined ? null : params.inner;
	var outer  = params.outer == undefined ? null : params.outer;
	var open = params.open == undefined ? null : params.open;
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
			if (this.status >= 200 && this.status < 300) {
				if (type.startsWith('application/json')) {
					if (success !== null) {
						success(JSON.parse(this.responseText));
					}
				} else if (type.startsWith('text/html')) {
					if (success == null && inner == null && outer == null && open == null) {
						document.write(this.responseText);
						document.close();
					} else {
						if (inner !== null) {
							document.getElementById(inner).innerHTML = this.responseText;
						} else if (outer !== null) {
							document.getElementById(outer).outerHTML = this.responseText;
						} else if (open !== null) {
							var reference = window.open('', open, '');
							reference.document.write(this.responseText);
							reference.document.close();
						}
						if (success !== null) {
							success(this.responseText);
						}
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
			} else if (this.status >= 300 && this.status < 400) {
				window.location.assign(this.getResponseHeader("Location"));
			} else if (this.status >= 400 && this.status < 500) {
				if (type.startsWith('application/json')) {
					var error = JSON.parse(this.responseText);
					if (failure !== null) {
						failure(error);
					} else {
						alertError(error);
					}
				}
			} else if (this.status >= 500) {
				error = {
					code   : this.status,
					reason : 'Server error'
				};
				if (failure !== null) {
					failure(error);
				} else {
					alertError(error);
				}
			}
		}
	};

	request.send(query);
	
}

var alertError = function(error) {
	if (error.message !== undefined) {
		alert(error.message);
	} else {
		alert(error.code + ' ' + error.reason);
	}
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

var clearProcess = function(pid, offset, callback) {
	invokeZord(
		{
			module:'Process',
			action:'clear',
			pid:pid,
			offset:offset,
			success:function(result) {
				if (callback !== undefined && callback !== null) {
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

var reportProcess = function(pid, style, indent, message, newline, over, callback) {
	invokeZord(
		{
			module:'Process',
			action:'report',
			line: JSON.stringify({
				process:pid,
				style:style,
				indent:indent,
				message:message,
				newline:newline,
				over:over
			}),
			success:function(result) {
				if (callback !== undefined) {
					callback(result);
				}
			}
		}
	);
}

var followProcess = function(follow) {
	var report = null;
	var step = null;
	var progress = null;
	var wait = null;
	var start = null;
	var stop = null;
	if (follow.offset == undefined || follow.offset == null) {
		follow.offset = 0;
	}
	if (follow.controls !== undefined && follow.controls !== null) {
		var controls = follow.controls;
		if (controls.report !== undefined && controls.report !== null) {
			report = controls.report;
		}
		if (controls.step !== undefined && controls.step !== null) {
			step = controls.step;
		}
		if (controls.progress !== undefined && controls.progress !== null) {
			progress = controls.progress;
		}
		if (controls.wait !== undefined && controls.wait !== null) {
			wait = controls.wait;
		}
		if (controls.start !== undefined && controls.start !== null) {
			start = controls.start;
		}
		if (controls.stop !== undefined && controls.stop !== null) {
			stop = controls.stop;
		}
	}
	var process = getProcess(follow);
	if (process == undefined || process == null) {
		if (step !== undefined && step !== null) {
			step.innerHTML = LOCALE.process.stopped;
		}
		if (wait !== undefined && wait !== null) {
			if (wait.style.display == 'block') {
				wait.style.display = 'none';
			}
		}
		if (report !== undefined && report !== null) {
			var lines = [['info',''],['error',LOCALE.process.stopped],['info','']];
			[].forEach.call(lines, function(line) {
				reportProcess(follow.process, line[0], 0, line[1], true, false);
			});
			checkProcess(follow.process, follow.offset, function(result) {
				[].forEach.call(result.report, function(line) {
					reportLine(report, line.style, line.indent, line.message, line.newline, line.over);
				});
			});
		}
		clearProcess(follow.process, follow.clear);
		if (follow.killed !== undefined && follow.killed !== null) {
			var killed = follow.killed;
			killed();
		}
		return;
	}
	checkProcess(follow.process, follow.offset, function(result) {
		if (result.error !== undefined) {
			alert(result.error);
			if (follow.error !== undefined && follow.error !== null) {
				var error = follow.error;
				error(result);
			}
		} else {
			if (report !== undefined && report !== null) {
				[].forEach.call(result.report, function(line) {
					reportLine(report, line.style, line.indent, line.message, line.newline, line.over);
				});
			}
			if (step !== undefined && step !== null) {
				if (result.step == 'closed') {
					step.innerHTML = LOCALE.process.closed;
				} else if (result.step == 'init') {
					step.innerHTML = LOCALE.process.init;
				} else {
					step.innerHTML = result.step;
				}
			}
			if (progress !== undefined && progress !== null) {
				progress.style = 'width:' + result.progress + '%;';
				progress.innerHTML = result.percent > 3 ? result.percent + '%' : '';
			}
			follow.offset = follow.offset + result.report.length;
			if (result.step !== 'closed') {
				setTimeout(followProcess, follow.period, follow);
			} else {	
				if (wait !== undefined && wait !== null) {
					wait.style.display = 'none';
				}
				if (start !== undefined && start !== null) {
					start.style.display = 'inline';
				}
				if (stop !== undefined && stop !== null) {
				    stop.style.display = 'none';
				}
				if (report !== undefined && report !== null) {
					reportLine(report, 'info', 0, '', true, false);
				}
				clearProcess(follow.process, follow.clear);
				var close = follow.close;
				if (close !== undefined && close !== null) {
					close();
				}
				setProcess(follow, null);
			}
		}
	});
}
	
var resetProcess = function(follow, process) {
	var notify = null;
	var step = null;
	var progress = null;
	var report = null;
	var wait = null;
	var start = null;
	var stop = null;
	var controls = follow.controls;
	var starting = process !== undefined && process !== null;
	if (controls !== undefined && controls !== null) {
		notify = controls.notify;
		step = controls.step;
		progress = controls.progress;
		report = controls.report;
		wait = controls.wait;
		start = controls.start;
		stop = controls.stop;
	}
	if (start !== undefined && start !== null) {
		if (starting) {
			start.style.display = 'none';
		} else {
			start.style.display = 'inline';
		} 
	}
	if (stop !== undefined && stop !== null) {
		if (starting) {
			stop.style.display = 'inline';
		} else {
			stop.style.display = 'none';
		}
	}
	if (notify !== undefined && notify !== null) {
		notify.style.display = 'block';
	}
	if (step !== undefined && step !== null) {
		step.innerHTML = '&nbsp;';
	}
	if (progress !== undefined && progress !== null) {
		progress.style = 'width:0;';
		progress.innerHTML = '';
	}
	if (report !== undefined && report !== null) {
		if (starting) { 
			elements = report.querySelectorAll('span,br');
			if (elements) {
				[].forEach.call(elements, function(element) {
					element.parentNode.removeChild(element);
				});
			}
	   		report.style.display = 'block';
		} else {
   			report.style.display = 'none';
		}
	}
	if (wait !== undefined && wait !== null) {
		if (starting) { 
	   		wait.style.display = 'block';
		} else {
			wait.style.display = 'none';
		}
	}
	if (starting) {
		setProcess(follow, process);
		follow.process = process;
		follow.offset  = 0;
	}
	return follow;
}

var getProcess = function(follow) {
	var key = 'process.' + follow.name;
	return getSessionProperty(key, null);
}

var setProcess = function(follow, pid) {
	var key = 'process.' + follow.name;
	setSessionProperty(key, pid);
}

var handleProcess = function(params, follow) {
	var pid = getProcess(follow);
	if (pid == undefined || pid == null) {
		params.success = function(result) {
			follow = resetProcess(follow, result);
			setTimeout(followProcess(follow), 200);
		};
		invokeZord(params);
	} else {
		var controls = follow.controls;
		if (follow.controls !== undefined && follow.controls !== null) {
			resetProcess({controls: {
				start: controls.start,
				stop : controls.stop
			}});
		}
		killProcess(pid);
		setProcess(follow, null);
	}
}
	
var reportLine = function(report, style, indent, message, newline, over) {
	span = document.createElement("span");
	span.classList.add(style);
	span.style.paddingLeft = (indent * 2) + "em";
	span.innerHTML = message;
	spinner = report.querySelector('.spinner');
	if (report.dataset.over == 'true') {
		if (spinner) {
			report.removeChild(report.lastElementChild.previousElementSibling);
	    } else {
			report.removeChild(report.lastElementChild);
		}
	}
	if (spinner) {
		report.insertBefore(span, spinner);
	} else {
		report.appendChild(span);
	}
	report.dataset.over = over ? 'true' : 'false';
	if (newline) {
		var br = document.createElement("br");
		if (spinner) {
			report.insertBefore(br, spinner);
		} else {
			report.appendChild(br);
		}
	}
	report.scrollTop = report.scrollHeight - report.clientHeight;
}

var setSessionProperties = function(zord) {
	localStorage.setItem('zord', JSON.stringify(zord));
	//dispatchSessionProperties();
}

var getSessionProperties = function() {
	zord = JSON.parse(localStorage.getItem('zord'));
	if (zord == undefined || zord == null) {
		zord = {};
		setSessionProperties(zord);
	}
	return zord;
}

/***************************************
 * Share zord properties between tabs
 * cf. https://stackoverflow.com/questions/20325763/browser-sessionstorage-share-between-tabs
 **************************************/

var dispatchSessionProperties = function() {
	localStorage.setItem('dispatchSessionProperties', 'foobar');
	localStorage.removeItem('dispatchSessionProperties');
}

var retrieveSessionProperties = function(event) {
	if (!event.newValue) {
		return;
	}
	if (event.key == 'dispatchSessionProperties') {
		if (sessionStorage.getItem('zord')) {
			localStorage.setItem('zordSessionProperties', sessionStorage.getItem('zord'));
			localStorage.removeItem('zordSessionProperties');
		}
	} else if (event.key == 'zordSessionProperties') {
		sessionStorage.setItem('zord', event.newValue);
	}
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
		_loadData(Object.assign(params, {action:action,async:false}), keys[json]);
	} else {
		invokeZord(Object.assign(params, {
			action  : 'hashKey',
			_action : action,
			async   : false,
			success : function(key) {
				keys[json] = key;
				setSessionProperty('data.keys', keys);
				_loadData(Object.assign(params, {action:action,async:false}), key);
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
