var PORTAL = getSessionProperty('portal.config', undefined);

window.$scrollTop = {
	set : function(value) {
		window.scrollTo(0, value);
	},
	get : function() {
		return document.body.scrollTop || document.documentElement.scrollTop;
	}
};

window.$dialog = (function(undefined) {

	var dialogID = '__dialog_';
	var dialogModalID = '__dialogModal_';
	
	var _topZIndex = function() {
			var num = [1];
			[].forEach.call(document.querySelectorAll('*'),function(el, i){
				var x = parseInt(window.getComputedStyle(el, null).getPropertyValue("z-index")) || null;
				if(x!=null)
					num.push(x);
			});
			return Math.max.apply(null, num)+1;
	};
	
	var _position = function(elem) {
		// selon la talle de l'élément détermine le top et left
		var top = ((window.innerHeight / 2) - (elem.offsetHeight / 2)) - 50;
		var left = ((window.innerWidth / 2) - (elem.offsetWidth / 2));

		// reste dans la fenêtre
		if( top < 0 ) top = 0;
		if( left < 0 ) left = 0;

		// css sur l'élément
		elem.style.top = top + 'px';
		elem.style.left = left + 'px';
	};

	var show  = function(msg,type,isModal,callback) {
		if (isModal) {
			modal();
		}
		var dialogEl = document.getElementById(dialogID);
		if (dialogEl==undefined) {
			document.body.insertAdjacentHTML('beforeend','<div class="dialog" id="'+dialogID+'"></div>');
			dialogEl = document.getElementById(dialogID);
		}
		dialogEl.style.zIndex = _topZIndex()+1;
		switch (type) {
			case 'box':
				dialogEl.innerHTML = msg;
			break;
			case 'waitMsg':
				dialogEl.innerHTML = msg;
				setTimeout(function(){
					dialog.hide();
				},1500);
			break;
		}
		if (callback != undefined) {
			callback(dialogEl);
		}

		_position(dialogEl);

		dialogEl.style.visibility = 'visible';
	};
	
	var modal = function() {
		var dialogModalEl = document.getElementById(dialogModalID);
		if (dialogModalEl == undefined) {
			document.body.insertAdjacentHTML('beforeend', '<div id="' + dialogModalID + '"></div>');
			dialogModalEl = document.getElementById(dialogModalID);
		}
		dialogModalEl.style.zIndex = _topZIndex();
		var body = document.body, html = document.documentElement;
		var height = Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight );
		dialogModalEl.style.height = height + 'px';
	};

	var dialog = {
		hide : function() {
			setTimeout(function() {
				var dialogEl = document.getElementById(dialogID);
				if(dialogEl)
					dialogEl.parentNode.removeChild( dialogEl );
				var dialogModalEl = document.getElementById(dialogModalID);
				if(dialogModalEl)
					dialogModalEl.parentNode.removeChild( dialogModalEl );
			},20);
		},
		hideDelay: function() {
			setTimeout(function() {
				dialog.hide();
			},350);
		},
		box : function(msg,callback) {
			show(msg,'box',true,callback);
		},
		waitMsg : function(msg,callback) {
			show(msg,'waitMsg',false,callback);
		},
		wait : function(callback) {
			show('<div class="dialog-wait"></div>','box',true,callback);
		},
		help : function(element) {
			show(document.getElementById('template_dialog_help').innerHTML, 'box', true, function(dialogEl) {
				dialogEl.querySelector('div[data-id="content"]').innerHTML = element.firstElementChild.innerHTML;
				dialogEl.querySelector('button[data-id="dialog_help_close"]').addEventListener("click", function(event) {
					$dialog.hide();
				});
			});
		}
	};
	return dialog;
}());

var getNumber = function(element,property) {
	var string = window.getComputedStyle(element).getPropertyValue(property);
	return Number(string.substring(0, string.length - 2));
};

var setWindowHeight = function() {
	windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
};

var activateChosen = function() {
	if (typeof PORTAL.chosen !== 'undefined') {
		for (var type in PORTAL.chosen) {
			$('.chosen-select-' + type).chosen(PORTAL.chosen[type]).change(function(event, params) {
				if (event.target.hasAttribute('data-change')) {
					method = event.target.getAttribute('data-change');
					if (window[method] instanceof Function) {
						window[method]();
					}
				}
		    });
		}
	}
}

var activateStates = function(element) {
	if (typeof PORTAL.states !== 'undefined') {
		[].forEach.call(element.querySelectorAll('.state'), function(entry) {
			entry.addEventListener("click", function(event) {
				types = entry.dataset.type.split('|');
				states = {};
				[].forEach.call(types, function(type) {
					states = Object.assign(states, PORTAL.states[type]);
				});
				keys = Object.keys(states);
				input = entry.querySelector('input');
				current = input.value;
				next = keys[(keys.indexOf(current) + 1) % keys.length]
				input.value = next;
				display = entry.querySelector('.display');
				display.classList.remove(states[current]);
				display.classList.add(states[next]);
			});
		});
	}
}

var displayAccount = function(action) {
	[].forEach.call(document.querySelectorAll('li.account'), function(element) {
		element.classList.remove('active');
		if (element.classList.contains(action)) {
			element.classList.add('active');
		}
	});
}

var swipeAt = function(element, index) {
	element.dataset.index = index.toString();
	var frame  = element.querySelector('.window');
	var slider = element.querySelector('.slider');
	if (frame && slider) {
		switch (element.dataset.transition) {
			case 'vertical': {
				height = frame.offsetHeight;
				position = -(index * height);
				slider.style.top = position + 'px';
				break;
			}
			case 'horizontal': {
				width = frame.offsetWidth;
				position = -(index * width);
				slider.style.left = position + 'px';
				break;
			}
			case 'crossfade': {
				height = frame.offsetHeight;
				position = -(index * height);
				slider.style.top = position + 'px';
				frames = element.querySelectorAll(element.dataset.frames);
				[].forEach.call(frames, function(frame) {
					frame.classList.remove('current');
				});
				frames[index].classList.add('current');
			}
		}
	}
}

var swipeTo = function(element, direction) {
	var frames = element.querySelectorAll(element.dataset.frames);
	var index  = Number.parseInt(element.dataset.index);
	switch (direction) {
		case 'backward': {
			if (index > 0) {
				index = index - 1;
			} else {
				index = frames.length - 1;
			}
			break;
		}
		case 'forward': {
			if (index < frames.length - 1) {
				index = index + 1;
			} else {
				index = 0;
			}
			break;
		}
	}
	swipeAt(element, index);
}

var swipeStart = function(swipe) {
	swipe.dataset.clear = setInterval(swipeTo, Number.parseInt(swipe.dataset.interval), swipe, 'forward');
}

var swipeStop = function(swipe) {
	if (swipe.dataset.clear !== undefined) {
		clearInterval(Number.parseInt(swipe.dataset.clear));
	}
}

document.addEventListener("DOMContentLoaded", function(event) {

	if (PORTAL == undefined) {
		invokeZord({
			module: 'Portal',
			action: 'config',
			async:  false,
			success: function(config) {
				PORTAL = config;
				setSessionProperty('portal.config', PORTAL);
			}
		});
	}

	window.addEventListener("selectLoaded", function(event) {
		selects = document.querySelectorAll('select[data-loading]');
		var loaded = true;
		[].forEach.call(selects, function(select) {
			loaded = (select.dataset.loading == 'true') ? false : loaded;
		});
		if (loaded) {
			activateChosen();
		}
	});
	
	[].forEach.call(document.querySelectorAll('.slide'), function(element) {
		element.addEventListener("mouseover", function(event) {
			element.classList.add("show");
		});
		element.addEventListener('mouseout', function(event) {
			element.classList.remove("show");
		});
	});
	
	[].forEach.call(document.querySelectorAll('.help_dialog'), function (el) {
		el.addEventListener("click", function(event) {
			$dialog.box(document.getElementById('template_dialog_help').innerHTML, function(dialogEl){
				dialogEl.querySelector('div[data-id="content"]').innerHTML = el.firstElementChild.innerHTML;
				dialogEl.querySelector('button[data-id="dialog_help_close"]')
					.addEventListener("click", function(event) {
						$dialog.hide();
					}
				);
			});
		});
	});
	
	[].forEach.call(document.querySelectorAll('a.mail'), function (el) {
		el.addEventListener("click", function(event) {
			window.location.href = 'mailto:' + this.dataset.name + '@' + this.dataset.domain + '.' + this.dataset.tld;
			return false;
		});
	});
	
	[].forEach.call(document.querySelectorAll('li.account div.switch'), function(element) {
		element.addEventListener("click", function(event) {
			displayAccount(element.dataset.action);
		});
	});
	
	[].forEach.call(document.querySelectorAll('.swipe'), function(swipe) {
		var forward  = swipe.querySelector('.forward');
		var backward = swipe.querySelector('.backward');
		if (forward && backward) {
			[].forEach.call([forward, backward], function(button) {
				button.addEventListener('click', function(event) {
					swipeTo(swipe, button.dataset.direction);
				});
			})
		}
	});
	
	[].forEach.call(document.querySelectorAll('.swipe[data-interval]:not([data-interval="0"])'), function(swipe) {
		swipeStart(swipe);
	});
	
});

