var CONFIG;
var LOCALE;

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
		hide : function(callback) {
			setTimeout(function() {
				var dialogEl = document.getElementById(dialogID);
				if(dialogEl)
					dialogEl.parentNode.removeChild( dialogEl );
				var dialogModalEl = document.getElementById(dialogModalID);
				if(dialogModalEl)
					dialogModalEl.parentNode.removeChild( dialogModalEl );
				if (callback !== undefined && callback !== null) {
					callback();
				}
			},20);
		},
		hideDelay: function(callback) {
			setTimeout(function() {
				dialog.hide(callback);
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
		help : function(element, callback) {
			show(document.getElementById('template_dialog_help').innerHTML, 'box', true, function(dialogEl) {
				dialogEl.querySelector('div[data-id="content"]').innerHTML = element.firstElementChild.innerHTML;
				dialogEl.querySelector('button[data-id="dialog_help_close"]').addEventListener("click", function(event) {
					$dialog.hide(callback);
				});
			});
		}
	};
	return dialog;
}());

var getNumber = function(element,property) {
	return Number(window.getComputedStyle(element).getPropertyValue(property).replace(/[^\d.]+/g, ''));
};

var getUnit = function(element,property) {
	return window.getComputedStyle(element).getPropertyValue(property).replace(/[\d.]+/g, '').trim();
};

var activateChosen = function() {
	if (typeof CONFIG.chosen !== 'undefined') {
		for (var type in CONFIG.chosen) {
			$('.chosen-select-' + type).chosen(CONFIG.chosen[type]).change(function(event, params) {
				if (event.target.hasAttribute('data-change')) {
					method = event.target.getAttribute('data-change');
					if (window[method] instanceof Function) {
						window[method](event, params);
					}
				}
		    });
			//Workaround for chosen clipped by overflow:hidden container
			//Adapted from https://jsfiddle.net/phil_ayres/gvn8bkaL/
			$('.chosen-select-' + type).on('chosen:showing_dropdown', function (event, params) {    
				// Access the element
				var $container = params.chosen.container;
				var element = $container[0];
				var style = element.style;
				var rect = element.getBoundingClientRect();
				// Save the original position and sizes
				$container.originalCSS = {
					width: $container.width(),
					height: $container.height(),
					position: style.position,
					top: style.top,
					left: style.left,
					zIndex: style.zIndex
				};
				// Set where we want to position the element
				var newCSS = $.extend({}, $container.originalCSS);
				newCSS.position = 'absolute';
				newCSS.top = rect.top + window.pageYOffset;
				newCSS.left = rect.left + window.pageXOffset;
				newCSS.zIndex = 999999;
				// Placeholder to the original position, plus it keeps the correct size for the form
				var $clone = $container.placeholder = $container.clone();
				$clone.find('.chosen-drop').remove();
				$container.before($clone);
				// Set the new position and move the chosen box into the body
				$container.css(newCSS);
				$('body').append($container);
			});
			$('.chosen-select-' + type).on('chosen:hiding_dropdown', function (event, params) {
			    // Move the chosen box back into its form, and remove the placeholder
				var $container = params.chosen.container;
				$container.css($container.originalCSS);
				$container.placeholder.before($container);
				$container.placeholder.remove();
			});
		}
	}
};

var activateStates = function(element) {
	if (typeof CONFIG.states !== 'undefined') {
		[].forEach.call(element.querySelectorAll('.state'), function(entry) {
			entry.addEventListener("click", function(event) {
				display = entry.querySelector('.display');
				input = entry.querySelector('input');
				types = entry.dataset.type;
				if (types == undefined || types == null) {
					types = input.name;
				}
				types = types.split('|');
				states = {};
				[].forEach.call(types, function(type) {
					states = Object.assign(states, CONFIG.states[type]);
				});
				keys = Object.keys(states);
				current = input.value;
				next = keys[(keys.indexOf(current) + 1) % keys.length]
				input.value = next;
				display.classList.remove(states[current]);
				display.classList.add(states[next]);
			});
		});
	}
};

var displayAccount = function(action) {
	[].forEach.call(document.querySelectorAll('li.account'), function(element) {
		element.classList.remove('active');
		if (element.classList.contains(action)) {
			element.classList.add('active');
		}
	});
};

var slideAt = function(element, index) {
	var change = element.dataset.index !== index.toString();
	element.dataset.index = index.toString();
	var slider = element.querySelector('.slider');
	var current = slider.querySelector("[data-slide='" + element.id + "'][data-index='" + index + "']");
	if (slider && current) {
		position = -(Number.parseInt(current.dataset.position));
		switch (element.dataset.direction) {
			case 'vertical': {
				slider.style.top = position + 'px';
				break;
			}
			case 'horizontal': {
				slider.style.left = position + 'px';
				break;
			}
		}
		switch (element.dataset.transition) {
			case 'crossfade': {
				frames = element.querySelectorAll(element.dataset.frames);
				[].forEach.call(frames, function(frame) {
					frame.classList.remove('current');
				});
				frames[index].classList.add('current');
				break;
			}
		}
		if (change) {
			element.dispatchEvent(new Event("change"));
		}
	}
	[].forEach.call(element.querySelectorAll(".controls span.index[data-slide='" + element.id + "']"), function(item) {
		item.classList.remove('highlight');
	});
	[].forEach.call(element.querySelectorAll(".controls span.index[data-slide='" + element.id + "'][data-index='" + index + "']"), function(item) {
		item.classList.add('highlight');
	});
};

var slideTo = function(element, direction) {
	var index   = Number.parseInt(element.dataset.index);
	var step    = Number.parseInt(element.dataset.step);
	var frames  = element.querySelectorAll(element.dataset.frames);
	var cells   = element.querySelectorAll('.cell').length;
	var padding = element.querySelectorAll('.cell.padding').length;
	var last    = (cells == 0 ? 1 : Math.round(cells / frames.length)) * (frames.length - 1) - padding;
	switch (direction) {
		case 'first': {
			index = 0;
			break;
		}
		case 'last': {
			index = last;
			break;
		}
		case 'backward': {
			index = index - step;
			if (index < 0) {
				if (element.dataset.limits == 'bounce') {
					index = last;
				} else {
					index = 0;
				}
			}
			break;
		}
		case 'forward': {
			index = index + step;
			if (index > last) {
				if (element.dataset.limits == 'bounce') {
					index = 0;
				} else if (frames.length > 1) {
					index = last;
				} else {
					index = 0;
				} 
			}
			break;
		}
	}
	slideAt(element, index);
};

var slideStart = function(element) {
	var interval = Number.parseInt(element.dataset.interval);
	if (interval > 0) {
		element.dataset.clear = setInterval(slideTo, interval, element, 'forward');
		[].forEach.call(element.querySelectorAll('.controls span.play'), function(play) {
			play.classList.add('highlight');
		});
		[].forEach.call(element.querySelectorAll('.controls span.pause'), function(pause) {
			pause.classList.remove('highlight');
		});
	}
};

var slideStop = function(element) {
	var clear = element.dataset.clear;
	if (clear !== undefined) {
		clearInterval(Number.parseInt(clear));
		[].forEach.call(element.querySelectorAll('.controls span.pause'), function(pause) {
			pause.classList.add('highlight');
		});
		[].forEach.call(element.querySelectorAll('.controls span.play'), function(play) {
			play.classList.remove('highlight');
		});
	}
};

var slidePosition = function(element) {
	[].forEach.call(element.querySelectorAll('.slide'), function(slide) {
		var frames = slide.querySelectorAll(slide.dataset.frames);
		var framePosition = 0;
		[].forEach.call(frames, function(frame) {
			var cells = frame.querySelectorAll('.cell');
			if (cells.length == 0) {
				frame.dataset.position = framePosition;
			} else {
				var cellPosition = framePosition;
				[].forEach.call(cells, function(cell) {
					cell.dataset.position = cellPosition;
					switch (slide.dataset.direction) {
						case 'vertical': {
							cellPosition += cell.offsetHeight;
							break;
						}
						case 'horizontal': {
							cellPosition += cell.offsetWidth;
							break;
						}
					}
				});
			}
			switch (slide.dataset.direction) {
				case 'vertical': {
					framePosition += frame.offsetHeight;
					break;
				}
				case 'horizontal': {
					framePosition += frame.offsetWidth;
					break;
				}
			}
		});
	});
};

var slideTimeout = function(slide) {
	timeout = (getUnit(slide, '--transition-duration') == 's' ? 1000 : 1) * getNumber(slide, '--transition-duration');
	timeout += (getUnit(slide, '--transition-delay') == 's' ? 1000 : 1) * getNumber(slide, '--transition-delay');
	return timeout;
}

var slideItem = function(slide, index) {
	return slide.querySelector(slide.dataset.frames + '[data-slide="' + slide.id + '"][data-index="' + (index !== undefined ? index : slide.dataset.index) + '"]');
}

document.addEventListener("DOMContentLoaded", function(event) {

	loadData({
		module : 'Portal',
		action : 'config'
	});
	CONFIG = getPortalProperty('config');

	loadData({
		module : 'Portal',
		action : 'locale',
		_lang  : LANG
	});
	LOCALE = getPortalProperty('locale.' + LANG);

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
	
	[].forEach.call(document.querySelectorAll('.pullout'), function(element) {
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
	
	[].forEach.call(document.querySelectorAll('.slide'), function(slide) {
		if (slide.dataset.index !== undefined) {
			slideAt(slide, Number.parseInt(slide.dataset.index));
		}
		var controls = JSON.parse(slide.dataset.controls);
		[].forEach.call(['forward', 'backward'], function(direction) {
			var control = slide.querySelector('.' + direction);
			if (control && controls[direction]) {
				control.addEventListener('click', function(event) {
					slideStop(slide);
					slideTo(slide, control.dataset.direction);
					slideStart(slide);
				});
			}
		});
		var frames = slide.querySelectorAll(slide.dataset.frames);
		[].forEach.call(['top','bottom','left','right'], function(position) {
			var index = slide.querySelector('.' + position);
			if (index) {
				var dynamic = Number.parseInt(slide.dataset.interval) > 0;
				if (dynamic && controls.pause) {
					item = document.createElement('span');
					item.classList.add('pause');
					item.addEventListener('click', function(event) {
						slideStop(slide);
					});
					index.appendChild(item);
				}
				[].forEach.call(frames, function(frame, num) {
					item = document.createElement('span');
					item.classList.add('index');
					item.dataset.index = num;
					item.dataset.slide = slide.id;
					if (frame.dataset.title !== undefined) {
						item.title = frame.dataset.title;
					}
					if (num == Number.parseInt(slide.dataset.index)) {
						item.classList.add('highlight');
					}
					if (controls.jump) {
						item.addEventListener('click', function(event) {
							slideStop(slide);
							slideAt(slide, num);
							slideStart(slide);
						});
					}
					index.appendChild(item);
				});
				if (dynamic && controls.play) {
					item = document.createElement('span');
					item.classList.add('play');
					item.addEventListener('click', function(event) {
						slideStart(slide);
					});
					index.appendChild(item);
				}
			}
		});
		var index = 0;
		[].forEach.call(frames, function(frame) {
			var cells = frame.querySelectorAll('.cell');
			if (cells.length == 0) {
				frame.dataset.index = index;
				frame.dataset.slide = slide.id;
				index++;
			} else {
				[].forEach.call(cells, function(cell) {
					cell.dataset.index = index;
					cell.dataset.slide = slide.id;
					index++;
				});
			}
		});
		slideStart(slide);
	});
	
});

window.addEventListener("load", function(event) {
		
	setTimeout(function() {
		loadings = document.querySelectorAll('div.loading');
		if (loadings) {
			[].forEach.call(loadings, function(loading) {
				load = loading.dataset.load;
				if (load !== undefined && window[load] !== undefined && typeof window[load] == 'function') {
					window[load](loading);
				}
			});
		}
	}, 300);
	
	const observer = new ResizeObserver(entries => {
		for (let entry of entries) {
			entry.target.parentNode.style.height = window.getComputedStyle(entry.target).getPropertyValue('height');
		}
	});
	[].forEach.call(document.querySelectorAll('ul.chosen-choices'), function(choices) {
		observer.observe(choices);
	});
	
	slidePosition(document);
	
});

