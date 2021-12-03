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
	
	[].forEach.call(document.querySelectorAll('#pagination li.cursor'), function(cursor) {
		cursor.addEventListener('click', function(event) {
			controls = cursor.parentNode;
			data = controls.dataset;
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
				lookup = document.querySelector('#lookup input');
				keyword = lookup ? lookup.value : '';
				current = cursor.parentNode.dataset.tab;
				invokeZord({
					module:'Admin',
					action:'index',
					tab:current,
					operation:'list',
					offset:offset,
					keyword:keyword.trim()
				});
			}
		});
	});
	
	[].forEach.call(document.querySelectorAll('#pagination li.index select'), function(index) {
		index.addEventListener('change', function(event) {
			lookup = document.querySelector('#lookup input');
			keyword = lookup ? lookup.value : '';
			current = index.parentNode.parentNode.dataset.tab;
			invokeZord({
				module:'Admin',
				action:'index',
				tab:current,
				operation:'list',
				offset:index.value,
				keyword:keyword.trim()
			});
		});
	});
	
	[].forEach.call(document.querySelectorAll('#lookup i'), function(button) {
		button.addEventListener('click', function(event) {
			keyword = button.previousElementSibling.value;
			current = button.parentNode.dataset.tab;
			if (keyword == undefined || keyword == null) {
				keyword = '';
			}
			invokeZord({
				module:'Admin',
				action:'index',
				tab:current,
				operation:'list',
				offset:0,
				keyword:keyword.trim()
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
