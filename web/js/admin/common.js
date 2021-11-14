function add(line) {
	var list = line.parentNode;
	var newLine = list.querySelector('.hidden').cloneNode(true);
	newLine.classList.remove('hidden');
	newLine.classList.add('data');
	list.appendChild(newLine);
	dress(newLine);
}

function remove(line) {
	var list = line.parentNode;
	list.removeChild(line);
}

function attach(operations, callback) {
	var opList = ['create','update','delete'];
	[].forEach.call(operations, function(op) {
		opList.push(op);
	});
	[].forEach.call(opList, function(operation) {
		[].forEach.call(document.querySelectorAll('.' + operation), function(entry) {				
			entry.addEventListener("click", function(event) {
				callback(entry, operation);
			});				
		});			
	});
}

function dress(element) {
	[].forEach.call(element.querySelectorAll('.add'), function(entry) {
		entry.addEventListener("click", function(event) {
			add(entry.parentNode);
		});
	});
	[].forEach.call(element.querySelectorAll('.remove'), function(entry) {
		entry.addEventListener("click", function(event) {
			remove(entry.parentNode);
		});
	});
	activateStates(element);
}
	
function reportLine(report, style, indent, message, newline, over) {
	span = document.createElement("span");
	span.classList.add(style);
	span.style.paddingLeft = (indent * 2) + "em";
	span.innerHTML = message;
	spinner = report.querySelector('.spinner');
	if (report.dataset.over == 'true') {
		report.removeChild(report.lastElementChild);
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

function followProcess(params) {
	if (params.stopped !== undefined && params.stopped !== null) {
		var stopped = params.stopped;
		if (stopped()) {
			return;
		}
	}
	checkProcess(params.process, params.offset, function(result) {
		if (result.error !== undefined && params.error !== undefined && params.error !== null) {
			var error = params.error;
			error(result);
		} else if (params.follow !== undefined && params.follow !== null) {
			var follow = params.follow;
			follow(result);
			params.offset = params.offset + result.report.length;
			if (result.step !== 'closed') {
				setTimeout(followProcess, params.period, params);
			} else if (params.closed !== undefined && params.closed !== null) {
				var closed = params.closed;
				closed();
			}
		}
	});
}


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

	[].forEach.call(document.querySelectorAll('.admin-list'), function(list) {
		var widths = list.getAttribute('data-columns').split(',');
		[].forEach.call(list.querySelectorAll('li'), function(line) {
			[].forEach.call(line.querySelectorAll('.column'), function(column, index) {
				column.style = "width:" + widths[index];
			});
		});
	});

	dress(document);
	
});

document.addEventListener("load", function(event) {

	lists = document.querySelectorAll('.admin-list');
	[].forEach.call(lists, function(list) {
		list.style.width = window.getComputedStyle(list.firstElementChild).width;
	});

});
