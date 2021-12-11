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

function attach(list, callback) {
	[].forEach.call(list.querySelectorAll('.action'), function(entry) {
		var action = null;
		for (index = 0; index < entry.classList.length; index++) {
			var className = entry.classList.item(index);
			if (className !== 'column' && className !== 'action') {
				action = className;
				break;
			}
		}
		if (action !== null) {			
			entry.addEventListener("click", function(event) {
				callback(entry, action);
			});
		}				
	});			
}

function dress(element) {
	[].forEach.call(element.querySelectorAll('.action.add'), function(entry) {
		entry.addEventListener("click", function(event) {
			add(entry.parentNode);
		});
	});
	[].forEach.call(element.querySelectorAll('.action.remove'), function(entry) {
		entry.addEventListener("click", function(event) {
			remove(entry.parentNode);
		});
	});
	activateStates(element);
}

window.extras = {};

function list(offset) {
	var panel = document.getElementById('panel');
	var tab = panel.dataset.tab;
	var params = {
		module:'Admin',
		action:tab,
		offset:offset,
		after: function() {
			dress(panel);
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
	
	dress(document);
	paginate();
	
});
