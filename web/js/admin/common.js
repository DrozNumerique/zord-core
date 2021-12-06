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

window.extras = {};

function list(offset) {
	var tab = document.getElementById('panel').dataset.tab;
	var params = {
		module:'Admin',
		action:tab,
		offset:offset,
		after: function() {
			invokeZord({
				module:'Admin',
				action:'paginate',
				type:tab,
				outer:'pagination',
				after:function() {
					paginate();
				}
			});
		}
	}
	if (window.extras[tab] !== undefined) {
		var extras = window.extras[tab];
		params = Object.assign(params, extras());
	}
	invokeZord(params);
}

function adjust(list) {
	var widths = list.getAttribute('data-columns').split(',');
	[].forEach.call(list.querySelectorAll('li'), function(line) {
		[].forEach.call(line.querySelectorAll('.column'), function(column, index) {
			column.style = "width:" + widths[index];
		});
	});
}

function paginate() {
	var pagination = document.getElementById('pagination');
	if (pagination) {
		var select = pagination.querySelector('select');
		if (select) {
			select.addEventListener('change', function(event) {
				list(select.value);
			});
		}
		[].forEach.call(pagination.querySelectorAll('.cursor'), function(cursor) {
			cursor.addEventListener('click', function(event) {
				data = cursor.parentNode.dataset;
				offset = Number.parseInt(data.offset);
				limit  = Number.parseInt(data.limit);
				count  = Number.parseInt(data.count);
				if (cursor.classList.contains('previous') && offset - limit >= 0) {
					offset -= limit;
				}
				if (cursor.classList.contains('next') && offset + limit < count) {
					offset += limit;
				}
				if (offset !== Number.parseInt(data.offset)) {
					list(offset);
				}
			});
		});
	}
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
		adjust(list);
	});
	
	paginate();
	dress(document);
	
});

document.addEventListener("load", function(event) {

	lists = document.querySelectorAll('.admin-list');
	[].forEach.call(lists, function(list) {
		list.style.width = window.getComputedStyle(list.firstElementChild).width;
	});

});
