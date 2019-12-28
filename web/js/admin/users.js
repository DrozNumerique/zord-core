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
	return true;
}

function getProfile() {
	var roles = [];
	var ips = [];
	var account = document.getElementById('account').value;
	[].forEach.call(document.getElementById('roles').querySelectorAll('.data'), function(entry) {
		roles.push({
			user:account,
			role:entry.children[0].firstElementChild.value,
			context:entry.children[1].firstElementChild.value,
			start:entry.children[2].firstElementChild.value,
			end:entry.children[3].firstElementChild.value
		}); 
	});
	[].forEach.call(document.getElementById('ips').querySelectorAll('.data'), function(entry) {
		ips.push({
			user:account,
			ip:entry.children[0].children[0].value + '.' + entry.children[0].children[1].value + '.' + entry.children[0].children[2].value + '.' + entry.children[0].children[3].value,
			mask:entry.children[1].firstElementChild.value,
			include:entry.children[2].firstElementChild.value
		}); 
	});
	var profile = {
		account:account,
		roles:JSON.stringify(roles),
		ips:JSON.stringify(ips)
	};
	return profile;
}
	
document.addEventListener("DOMContentLoaded", function(event) {

	attach(['profile'], function(entry, operation) {
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
	
	var submitProfile = document.getElementById('submit-profile');
	if (submitProfile != undefined) {
		submitProfile.addEventListener("click", function(event) {
			var profile = getProfile();
			invokeZord({
				module:'Admin',
				action:'profile',
				account:profile.account,
				roles:profile.roles,
				ips:profile.ips
			});
		});
	}
});