var cache = {};
var eventId = 0;

resize();
$(window).resize(function(e){
	resize();
});

$('#iconbar a').bind('click', function(e){
	e.preventDefault();
	getPage($(e.currentTarget).attr('href'));
});

$('body').delegate('.more a', 'click', function(e){
	e.preventDefault();
	getPage($(e.currentTarget).attr('href'), true, 'monitor/');
});

$('body').delegate('#sidebar #event-list li', 'click', function(e){
	e.preventDefault();
	getEvent($(e.currentTarget).attr('id').replace('event-', ''));
});

$('body').delegate('#event-tabs a, #event-details .show-map', 'click', function(e){
	e.preventDefault();
	getPage($(e.currentTarget).attr('href'), false, 'monitor/');
});

$('body').delegate('#filter-list li a', 'click', function(e){
	e.preventDefault();
	if(!$(e.currentTarget).hasClass('add-filter')){
		toggleFilter($(e.currentTarget));
	}
});

$('body').delegate('.add-filter, .manage-filters', 'click', function(e){
	e.preventDefault();
	popover('manage_filters');
});

$('body').delegate('#settings_menu li a', 'click', function(e){
	e.preventDefault();
	getPage($(e.currentTarget).attr('href'), true, 'instellingen/');
});

$('body').delegate('#process-list td > a, #process-list .buttons a', 'click', function(e){
	e.preventDefault();
	get($(e.currentTarget).attr('href'), function(response){
		sleep(1);
		getPage('instellingen/processen/', false, 'instellingen/');
	});
});

$('body').delegate('#process-list .table-controls a.reload', 'click', function(e){
	e.preventDefault();
	getPage('instellingen/processen/', false, 'instellingen/');
});

$('body').delegate('#add-filter', 'submit', function(e){
	e.preventDefault();
	postForm($(e.currentTarget), function(r){
		get('manage_filters', function(response){
			reloadPopover(response);
		}, 'html');
		getPage(r.referer, false);
	});
});

$('body').delegate('#manage-filters a.delete-filter', 'click', function(e){
	e.preventDefault();
	$.post($(e.currentTarget).attr('href'), {'id': $(e.currentTarget).attr('rel')}, function(r){
		get('manage_filters', function(response){
			reloadPopover(response);
		}, 'html');
		getPage(r.referer, false);
	}, 'json');
});

$('body').delegate('#event-list .add-event', 'click', function(e){
	e.preventDefault();
	popover('add_event');
});

$('body').delegate('#event-list table a', 'click', function(e){
	e.preventDefault();
	if($(e.currentTarget).attr('rel') && $(e.currentTarget).attr('rel').length > 0){
		$.post($(e.currentTarget).attr('href'), {'id': $(e.currentTarget).attr('rel')}, function(response){
			getPage(response.referer, false);
		}, 'json');
	} else {
		popover($(e.currentTarget).attr('href'));
	}
});

$('body').delegate('.message a', 'click', function(e){
	e.preventDefault();
	popover($(e.currentTarget).attr('href'));
});

$('body').delegate('a.hashtag', 'click', function(e){
	e.preventDefault();
	$.post('do/filter/add/', {'type': 'term', 'value': $(e.currentTarget).text()}, function(r){
		getPage(r.referer, false);
	}, 'json');
});

$('body').delegate('a.mention', 'click', function(e){
	e.preventDefault();
	$.post('do/filter/add/', {'type': 'term', 'value': $(e.currentTarget).text()}, function(r){
		getPage(r.referer, false);
	}, 'json');
});

$('body').delegate('.gallery li > a', 'click', function(e){
	e.preventDefault();
	popover('popover_media?url='+$(e.currentTarget).attr('href'));
});

function postForm(form, callback){
	$.post(form.attr('action'), form.serialize(), function(response){
		callback(response);
	}, 'json');
}

function toggleFilter(filter){
	get(filter.attr('href'), function(response){
		if(response.toggle == "deactivate"){
			filter.parent().removeClass('selected');
		} else {
			filter.parent().addClass('selected');
		}
		getPage(response.referer, false, 'monitor/', false);
	});
}

function getEvent(id){
	var open_monitor = false;
	if($('#sidebar #event-list li.selected').length == 0){
		open_monitor = true;
	}
	$('#event-list li').removeClass('selected');
	get('do/models/Event/select/?id='+id, function(response){
		eventId = id;
		if(open_monitor){
			getPage('monitor/');
		} else {
			getPage(response.referer, false, 'monitor/');
		}
		$('#event-list #event-'+id).addClass('selected');
	});
}

function popover(url){
	if($('#popover').length > 0){
		$('#popover').remove();
	}
	if($('#event-tabs').length > 0){
		$('#main').append('<div class="loading">&nbsp;</div>');
	}
		
	if(cache[url]){
		$('body').append(cache[url]);
		$('#main > .loading').remove();
	} else {
		get(url, function(response){
			cache[url] = response;
			$('#main > .loading').remove();
			$('body').append(response);
		}, 'html');
	}
}

function reloadPopover(data){
	$('#popover').replaceWith(data);
}

function get(url, f, returnType){
	if(returnType == null){
		returnType = 'json';
	}
	$.get(url, function(response){
		f(response);
	}, returnType);
}

function getPage(href, includeSidebar, iconbarSelect, useCache){
	if($('#event-tabs').length > 0){
		$('#main').append('<div class="loading">&nbsp;</div>');
	}
	if(includeSidebar == null){
		includeSidebar = true;
	}
	if(iconbarSelect == null){
		iconbarSelect = href;
	}
	if(useCache == null){
		useCache = true;
	}
	
	if(href.substring(href.length-1, href.length) != '/'){
		var pointer = href+'/'+eventId;
	} else {
		var pointer = href+eventId;
	}
	if(useCache && cache[pointer]){
		loadPageFromResponse(cache[pointer], includeSidebar, iconbarSelect);
	} else {
		get('do/models/Page/find/?uri='+href, function(response){
			cache[pointer] = response;
			loadPageFromResponse(response, includeSidebar, iconbarSelect);
		});
	}
	
}

function loadPageFromResponse(response, includeSidebar, iconbarSelect){
	if(includeSidebar){
		if(response.wrapper_sidebar !== undefined){
			setSidebarContent(response.wrapper_sidebar);
		} else {
			setSidebarContent('<div id="sidebar"></div>');
		}
	}
	if(response.wrapper_main !== undefined){
		setMainContent(response.wrapper_main);
	} else {
		setMainContent('<div id="main"></div>');
	}
	$('#main > .loading').remove();
	$('#iconbar li').removeClass('selected');
	$('#iconbar a[href="'+iconbarSelect+'"]').parent().addClass('selected');
	document.title = response.page_title;
	if(response.page_uri.length == 0){
		response.page_uri = '.';
	}
	window.history.pushState('Object', response.page_title, response.page_uri);
}

function setSidebarContent(content){
	$('#sidebar').replaceWith(content);
}

function setMainContent(content){
	if($('#main').length == 0){
		$('#sidebar').after(content);
	} else {
		$('#main').replaceWith(content);
	}
	resize();
}

function sleep(sec) {
	var milliseconds = sec * 1000;
	var start = new Date().getTime();
	for (var i = 0; i < 1e7; i++) {
		if ((new Date().getTime() - start) > milliseconds){
			break;
		}
	}
}

function resize(){
	var width = $('body').outerWidth(true) - ($('#sidebar').outerWidth(true) + $('#iconbar').outerWidth(true));
	if($('.event-details').length > 0){
		var height = $('body').outerHeight(true) - ($('.event-details').outerHeight(true) + $('#event-tabs').outerHeight(true) + $('#filter-list').outerHeight(true));
	} else {
		var height = $('body').outerHeight(true) - ($('#scoreboard').outerHeight(true));
	}
	
	if($('#main').length > 0)$('#main').css('width', width+'px');
	if($('#map_canvas').length > 0) $('#map_canvas').css('width', width+'px');
	if($('#map_canvas').length > 0) $('#map_canvas').css('height', height+'px');
	if($('#messages').length > 0) $('#messages').css('height', height+'px');
	
	if($('.gallery').length > 0){
		var width = calculatePhotoWidth($('.gallery').innerWidth(), 5);
		$('.gallery li > a, .gallery li > a > *').css('height', width+'px');
		$('.gallery li > a, .gallery li > a > *').css('width', width+'px');
	}
}

function calculatePhotoWidth(containerWidth, n){
	var width = (containerWidth / n) - 31;
	if((containerWidth / n) - 31 < 120){
		width = calculatePhotoWidth(containerWidth, n-1);
	}
	return width;
}