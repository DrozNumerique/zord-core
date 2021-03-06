function checkAccount(operation, data) {
	if (data == undefined || data == null) {
		return false;
	}
	if (data.login == undefined || data.login == null || data.login.length == 0) {
		return false;
	}
	if (data.name == undefined || data.name == null || data.name.length == 0) {
		return false;
	}
	if (data.email == undefined || data.email == null || data.email.length == 0) {
		return false;
	}
	if (operation == 'delete') {
		return confirm(LOCALE.admin.users.delete.confirm);
	}
	return true;
}

function getProfile() {
	var roles = [];
	var ipv4 = [];
	var ipv6 = [];
	var login = document.getElementById('login').value;
	[].forEach.call(document.getElementById('roles').querySelectorAll('.data'), function(entry) {
		roles.push({
			user:login,
			role:entry.children[0].firstElementChild.value,
			context:entry.children[1].firstElementChild.value,
			start:entry.children[2].firstElementChild.value,
			end:entry.children[3].firstElementChild.value
		}); 
	});
	[].forEach.call(document.getElementById('ipv4').querySelectorAll('.data'), function(entry) {
		ipv4.push({
			user:login,
			ip:entry.children[0].children[0].value + '.' + entry.children[0].children[1].value + '.' + entry.children[0].children[2].value + '.' + entry.children[0].children[3].value,
			mask:entry.children[1].firstElementChild.value,
			include:entry.children[2].firstElementChild.value
		}); 
	});
	[].forEach.call(document.getElementById('ipv6').querySelectorAll('.data'), function(entry) {
		ipv6.push({
			user:login,
			ip:entry.children[0].children[0].value + ':' + entry.children[0].children[1].value + ':' + entry.children[0].children[2].value + ':' + entry.children[0].children[3].value + ':' + entry.children[0].children[4].value + ':' + entry.children[0].children[5].value + ':' + entry.children[0].children[6].value + ':' + entry.children[0].children[7].value,
			mask:entry.children[1].firstElementChild.value,
			include:entry.children[2].firstElementChild.value
		}); 
	});
	var profile = {
		login:login,
		roles:JSON.stringify(roles),
		ipv4:JSON.stringify(ipv4),
		ipv6:JSON.stringify(ipv6)
	};
	return profile;
}
	
document.addEventListener("DOMContentLoaded", function(event) {

	attach(['profile','notify'], function(entry, operation) {
		var data = {
			login:entry.parentNode.children[0].firstElementChild.value,
			name:entry.parentNode.children[1].firstElementChild.value,
			email:entry.parentNode.children[2].firstElementChild.value
		};
		if (checkAccount(operation, data)) {
			invokeZord({
				module:'Admin',
				action:'account',
				operation:operation,
				login:data.login,
				name:data.name,
				email:data.email
			});
		}
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
				keyword = document.querySelector('#lookup input').value;
				invokeZord({
					module:'Admin',
					action:'index',
					tab:'users',
					operation:'list',
					offset:offset,
					keyword:keyword.trim()
				});
			}
		});
	});
	
	[].forEach.call(document.querySelectorAll('#pagination li.index select'), function(index) {
		index.addEventListener('change', function(event) {
			keyword = document.querySelector('#lookup input').value;
			invokeZord({
				module:'Admin',
				action:'index',
				tab:'users',
				operation:'list',
				offset:index.value,
				keyword:keyword.trim()
			});
		});
	});
	
	[].forEach.call(document.querySelectorAll('#lookup i'), function(button) {
		button.addEventListener('click', function(event) {
			keyword = button.previousElementSibling.value;
			if (keyword == undefined || keyword == null) {
				keyword = '';
			}
			invokeZord({
				module:'Admin',
				action:'index',
				tab:'users',
				operation:'list',
				offset:0,
				keyword:keyword.trim()
			});
		});
	});
	
	var submitProfile = document.getElementById('submit-profile');
	if (submitProfile != undefined) {
		submitProfile.addEventListener("click", function(event) {
			var profile = getProfile();
			invokeZord({
				module:'Admin',
				action:'profile',
				login:profile.login,
				roles:profile.roles,
				ipv4:profile.ipv4,
				ipv6:profile.ipv6
			});
		});
	}
});