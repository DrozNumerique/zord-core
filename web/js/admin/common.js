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

	dress(document);
	
});

document.addEventListener("load", function(event) {

	lists = document.querySelectorAll('.admin-list');
	[].forEach.call(lists, function(list) {
		list.style.width = window.getComputedStyle(list.firstElementChild).width;
	});

});
