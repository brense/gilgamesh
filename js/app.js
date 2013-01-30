var views = {}
var mapData = {};

var selectedEvent;

var lastLoadedMap = 0;
var lastLoadedStats = 0;
var lastLoadedMedia = 0;
var lastLoadedReports = 0;

var map;
var heatmap;
var markerCluster;

/* INIT */
resize();
$(window).resize(function(e){
	resize();
});

var script = document.createElement("script");
script.type = "text/javascript";
script.src = "https://maps.googleapis.com/maps/api/js?sensor=false&libraries=visualization&callback=initializeMap";
document.body.appendChild(script);

getView('EventListView', $('#events'), function(response, target){
	target.replaceWith(response);
	getView('FilterListView', $('#filter-list'), null, false);
	if($('#event-list .selected').attr('id') != null){
		selectedEvent = $('#event-list .selected').attr('id').replace('event-', '');
	}
	switchTab($('#event-tabs .selected a').attr('class'));
});

window.setInterval(getNewUpdates, 10000);

/* DELEGATES */
$('body').delegate('a', 'click', function(e){
	e.preventDefault();
});

$('body').delegate('#iconbar li a', 'click', function(e){
	e.preventDefault();
	switch($(e.currentTarget).attr('href')){
		case 'instellingen/': openSettings(); break;
		case 'berichten/': openMessages(); break;
		case 'monitor/': openMonitor(); break;
		case '': openDashboard(); break;
	}
	$('#iconbar li').removeClass('selected');
	$(e.currentTarget).parent().addClass('selected');
});

$('body').delegate('#sidebar #event-list li', 'click', function(e){
	e.preventDefault();
	if($(e.currentTarget).attr('id') != null){
		selectEvent($(e.currentTarget).attr('id').replace('event-', ''));
	}
});

$('body').delegate('#event-tabs a', 'click', function(e){
	e.preventDefault();
	switchTab($(e.currentTarget).attr('class'));
});

$('body').delegate('#filter-list li a', 'click', function(e){
	e.preventDefault();
	if(!$(e.currentTarget).hasClass('manage-filters')){
		toggleFilter($(e.currentTarget));
	} else {
		getView('FilterManageView', $('#popover'), function(response, target){
			popover(response);
		}, false);
	}
});

$('body').delegate('#add-filter', 'submit', function(e){
	e.preventDefault();
	postForm($(e.currentTarget), function(response){
		getView('FilterManageView', $('#popover'), function(response){
			reloadPopover(response);
		}, false);
		getView('FilterListView', $('#filter-list'), null, false);
	});
});

$('body').delegate('#manage-filters a.delete-filter', 'click', function(e){
	e.preventDefault();
	$.get($(e.currentTarget).attr('href'), function(response){
		getView('FilterManageView', $('#popover'), function(response){
			reloadPopover(response);
		}, false);
		getView('FilterListView', $('#filter-list'), null, false);
		mapData[selectedEvent] = null;
		switchTab($('#event-tabs li.selected a').attr('class'));
	}, 'json');
});

$('body').delegate('.hashtag', 'click', function(e){
	e.preventDefault();
	var data = {};
	data.value = $(e.currentTarget).clone().children().remove().end().text().replace('#', '');
	data.field = 'entities.hashtags.text';
	if($(e.currentTarget).attr('rel') != null){
		data.event_id = $(e.currentTarget).attr('rel');
	} else {
		data.event_id = selectedEvent;
	}
	$.post('do/models/Filter/add', data, function(response){
		getView('FilterListView', $('#filter-list'), null, false);
	});
});

$('body').delegate('.show-user', 'click', function(e){
	e.preventDefault();
	loadingUpdates();
	$('#event-tabs li').removeClass('selected');
	$('#messages').show();
	$('#event-tabs a.updates').parent().addClass('selected');
	
	var userId = $(e.currentTarget).attr('rel');
	var url = 'do/models/Update/user?id='+userId;
	
	$.get(url, function(response){
		$('#messages').replaceWith(response);
	});
});

$('body').delegate('.gallery a', 'click', function(e){
	e.preventDefault();
	popover('<img src="'+$(e.currentTarget).attr('href')+'" alt="" />');
});

$('body').delegate('#messages .more a', 'click', function(e){
	e.preventDefault();
	$.get('do/views/MessageListView/render'+$(e.currentTarget).attr('href'), function(response){
		$('#messages .more').remove();
		$('#messages .scroll-container').append(response);
	});
});

$('body').delegate('.message-actions a.reply, .message-actions a.retweet', 'click', function(e){
	e.preventDefault();
	$.get('do/views/MessageView/render'+$(e.currentTarget).attr('href'), function(response){
		popover(response);
	});
});

$('body').delegate('.message-actions a.favorite', 'click', function(e){
	e.preventDefault();
	var postData = {id: $(e.currentTarget).attr('rel')};
	if($(e.currentTarget).find('span').hasClass('active')){
		postData.unfav = true;
	}
	$.post('do/models/Update/favorite', postData, function(response){
		if(postData.unfav != null){
			$(e.currentTarget).find('span').removeClass('active');
		} else {
			$(e.currentTarget).find('span').addClass('active');
		}
	});
});

$('body').delegate('.message-actions a.assign', 'click', function(e){
	e.preventDefault();
	$.post('do/models/Update/assignTo', {id: $(e.currentTarget).attr('rel')}, function(response){
		console.log(response);
	});
});

$('body').delegate('.message-actions a.flag', 'click', function(e){
	e.preventDefault();
	var postData = {id: $(e.currentTarget).attr('rel')};
	if($(e.currentTarget).hasClass('active')){
		postData.unflag = true;
	}
	$.post('do/models/Update/flag', postData, function(response){
		if(postData.unflag != null){
			$(e.currentTarget).removeClass('active');
		} else {
			$(e.currentTarget).addClass('active');
		}
	});
});

$('body').delegate('.message-actions a.read', 'click', function(e){
	e.preventDefault();
	var postData = {id: $(e.currentTarget).attr('rel')};
	if($(e.currentTarget).hasClass('active')){
		postData.unread = true;
	}
	$.post('do/models/Update/markAsRead', postData, function(response){
		if(postData.unread != null){
			$(e.currentTarget).removeClass('active');
		} else {
			$(e.currentTarget).addClass('active');
		}
	});
});

$('body').delegate('#popover form.form', 'submit', function(e){
	e.preventDefault();
	switch($(e.currentTarget).attr('method')){
		case 'get':
			$.get($(e.currentTarget).attr('action'), $(e.currentTarget).serialize(), function(response){
				
			});
			break;
		case 'post':
			$.post($(e.currentTarget).attr('action'), $(e.currentTarget).serialize(), function(response){
				
			});
			break;
	}
	$('#popover').remove();
});

$('body').delegate('#reply-form', 'submit', function(e){
	e.preventDefault();
	$.post($(e.currentTarget).attr('action'), $(e.currentTarget).serialize(), function(response){
		$('#message-' + response.id.replace('/', '-') + ' .meta.' + response.state + ' span').addClass('active');
	}, 'json');
	$('#popover').remove();
});

$('body').delegate('.message-body .url', 'click', function(e){
	e.preventDefault();
	window.open($(e.currentTarget).attr('href'));
});

$('body').delegate('#settings-menu a', 'click', function(e){
	e.preventDefault();
	$('.menu li').removeClass('selected');
	switch($(e.currentTarget).attr('href')){
		case 'instellingen/': openSettings(); break;
		case 'instellingen/processen/': openSettings('processes'); break;
		case 'instellingen/bronnen/': openSettings('sources'); break;
		case 'instellingen/gebeurtenissen/': openSettings('events'); break;
		case 'instellingen/filters/': openSettings('filters'); break;
	}
	$(e.currentTarget).parent().addClass('selected');
});

$('body').delegate('#settings table a.action', 'click', function(e){
	e.preventDefault();
	$.get($(e.currentTarget).attr('href'), function(response){
		console.log(response);
	});
});

$('body').delegate('#settings table a.popover', 'click', function(e){
	e.preventDefault();
	$.get($(e.currentTarget).attr('href'), function(response){
		popover(response);
	});
});

/* FUNCTIONS */

// get a view
function getView(view, target, callback, cache){
	if(cache == null){
		cache = true;
	}
	if(views[view] == null){
		$.get('do/views/'+view+'/render', function(response){
			if(cache){
				views[view] = response;
			}
			if(callback != null){
				callback(response, target);
			} else {
				target.replaceWith(response);
			}
		}, 'html');
	} else {
		if(callback != null){
			callback(views[view], target);
		} else {
			target.replaceWith(views[view]);
		}
	}
}

function switchTab(tab){
	$('#event-tabs li').removeClass('selected');
	switch(tab){
		case 'map': loadMap(selectedEvent); break;
		case 'updates': loadUpdates(selectedEvent); break;
		case 'media': loadMedia(selectedEvent); break;
		case 'stats': loadStats(selectedEvent); break;
		case 'reports': loadReports(selectedEvent); break;
	}
	$('#event-tabs a.'+tab).parent().addClass('selected');
}

function loadMap(eventId){
	// hide other windows
	$('#messages').hide();
	$('#event-stats').hide();
	$('#event-media').hide();
	$('#event-reports').hide();
	
	// check if new map should be loaded or if old one can be used
	if(eventId == lastLoadedMap && mapData[eventId] != null){
		return true;
	}
	lastLoadedMap = eventId;
	
	$('#main').append('<div class="loading"></div>');
	
	// get geodata
	if(mapData[eventId] == null){
		$.get('do/models/Event/geoData?event='+eventId, function(response){
			mapData[eventId] = response.data;
			loadMapMarkers(mapData[eventId]);
			selectedEvent = eventId;
		}, 'json');
	} else {
		selectedEvent = eventId;
		loadMapMarkers(mapData[eventId]);
	}
}

function loadUpdates(eventId){
	// hide other windows
	$('#event-stats').hide();
	$('#event-media').hide();
	$('#event-reports').hide();
	
	loadingUpdates();
	$('#messages').show();
	
	getView('MessageListView', $('#messages'), null, false);
}

function loadMedia(eventId){
	// hide other windows
	$('#event-stats').hide();
	$('#messages').hide();
	$('#event-reports').hide();
	
	if(eventId == lastLoadedMedia){
		$('#event-media').show();
		return true;
	}
	lastLoadedMedia = eventId;
	
	loadingMedia();
	$('#event-media').show();
	
	getView('EventMediaView', $('#event-media'), null, false);
}

function loadStats(eventId){
	// hide other windows
	$('#messages').hide();
	$('#event-media').hide();
	$('#event-reports').hide();
	
	if(eventId == lastLoadedStats){
		$('#event-stats').show();
		return true;
	}
	lastLoadedStats = eventId;
	
	loadingStats();
	$('#event-stats').show();
	
	getView('EventStatsView', $('#event-stats'), null, false);
}

function loadReports(eventId){
	// hide other windows
	$('#messages').hide();
	$('#event-media').hide();
	$('#event-stats').hide();
	
	if(eventId == lastLoadedReports){
		$('#event-reports').show();
		return true;
	}
	lastLoadedReports = eventId;
	
	loadingReports();
	$('#event-reports').show();
	
	getView('EventReportsView', $('#event-reports'), null, false);
}

/* OTHER FUNCTIONS */

function postForm(form, callback){
	$.post(form.attr('action'), form.serialize(), function(response){
		callback(response);
	}, 'json');
}

function popover(html){
	if($('#popover').length > 0){
		reloadPopover(html);
	} else {
		$.get('templates/popover.php', function(response){
			$('body').append(response);
			reloadPopover(html);
		});
	}
}

function reloadPopover(html){
	$('#popover .popover_content').html(html);
}

function selectEvent(eventId){
	$('#event-list li').removeClass('selected');
	selectedEvent = eventId;
	loadingFilters();
	$.get('do/models/Event/select/?eventId='+eventId, function(response){
		switchTab($('#event-tabs li.selected a').attr('class'));
		$('#event-list #event-'+eventId).addClass('selected');
		getView('FilterListView', $('#filter-list'), null, false);
	}, 'json');
}

function toggleFilter(filter){
	$.get(filter.attr('href'), function(response){
		if(response.toggle == "deactivate"){
			filter.parent().removeClass('selected');
		} else {
			filter.parent().addClass('selected');
		}
		mapData[selectedEvent] = null;
		lastLoadedStats = 0;
		lastLoadedMedia = 0;
		getView('FilterListView', $('#filter-list'), null, false);
		switchTab($('#event-tabs li.selected a').attr('class'));
	}, 'json');
}

function initializeMap() {
	var mapOptions = {
		zoom: 9,
		center: new google.maps.LatLng(52.523742, 5.466866),
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		zoomControlOptions: {
			position: google.maps.ControlPosition.RIGHT_TOP
		},
		panControlOptions: {
			position: google.maps.ControlPosition.RIGHT_TOP
		}
	};
	
	map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
}

function loadMapMarkers(arr){
	var markers = [];
	var heatmapData = [];

	// make markers for all the geo data
	var n = 0;
	for(var i in arr){
		var position = new google.maps.LatLng(arr[i][0], arr[i][1]);
		heatmapData.push(position);
		
		var marker = new google.maps.Marker({
			position: position,
			title: i
		});
		
		markers.push(marker);
		
		google.maps.event.addListener(markers[n], "click", function(){
			showGeoMessages(this.getTitle())
		});
		
		n++;
	}
	
	// create the heatmap
	if(heatmap instanceof google.maps.visualization.HeatmapLayer){
		heatmap.setMap(null);
	}
	heatmap = new google.maps.visualization.HeatmapLayer({
		data: heatmapData,
		radius: 50
	});
	heatmap.setMap(map);
	
	// create the marker cluster
	if(markerCluster instanceof MarkerClusterer){
		markerCluster.clearMarkers();
	}
	markerCluster = new MarkerClusterer(map, markers, {maxZoom: 19, zoomOnClick: false, avarageCenter: true});
	
	if($('#main > .loading').length > 0){
		$('#main > .loading').remove();
	}
		
	google.maps.event.addListener(markerCluster, "click", function(cluster){
		var ids = [];
		var markers = cluster.getMarkers();
		for(var n = 0; n < markers.length; n++){
			ids.push(markers[n].getTitle());
		}
		showGeoMessages(ids);
	});
}

function showGeoMessages(latlng, latlng2){
	loadingUpdates();
	$('#event-tabs li').removeClass('selected');
	$('#messages').show();
	$('#event-tabs a.updates').parent().addClass('selected');
	
	if(latlng instanceof google.maps.LatLng && latlng2 == null){
		var url = 'do/models/Update/geo?lat='+latlng.lat()+'&long='+latlng.lng();
	} else if(latlng2 == null && latlng instanceof Array){
		var ids = latlng.join(',');
		var url = 'do/models/Update/find_by_ids?ids='+ids;
	} else if(latlng2 == null){
		var url = 'do/models/Update/find_by_id?id='+latlng;
	} else {
		var url = 'do/models/Update/geo?startlat='+latlng.lat()+'&startlong='+latlng.lng()+'&endlat='+latlng2.lat()+'&endlong='+latlng2.lng();
	}
	$.get(url, function(response){
		$('#messages').replaceWith(response);
	});
}

function getNewUpdates(){
	if($('#messages .message').length > 0){
		var updates = $('#messages .message');
		var timestamp = $(updates[0]).find('.timestamp').text();
		if(timestamp != null && timestamp > 0){
			$.get('do/views/MessageListView/render?timestamp='+timestamp, function(response){
				$('#messages .scroll-container').prepend(response);
			});
		}
	}
}

function openSettings(tab){
	if(tab == null){
		hideWindows();
		$('body #sidebar').append('<div id="settings-menu" class="menu"></div>');
		getView('SettingsMenuView', $('#settings-menu'), null, false);
		$('body #main').append('<div id="settings" class="full"></div>');
		getView('SettingsView', $('#settings'), null, false);
	} else {
		$('#settings > div').hide();
		$('#settings > .' + tab).show();
	}
}

function openMessages(){
	hideWindows();
	$('body #main').append('<div id="settings" class="full"></div>');
	$('body #sidebar').append('<div id="settings-menu" class="menu"></div>');
}

function openMonitor(){
	hideWindows();
	$('#event-tabs').show();
	$('#filter-list').show();
}

function openDashboard(){
	hideWindows();
	$('body #main').append('<div id="settings" class="full"></div>');
	$('body #sidebar').append('<div id="settings-menu" class="menu"></div>');
}

function hideWindows(){
	$('#event-tabs').hide();
	$('#filter-list').hide();
	if($('body #settings').length > 0){
		$('body #settings').remove();
	}
	if($('body #inbox').length > 0){
		$('body #inbox').remove();
	}
	if($('body #dashboard').length > 0){
		$('body #dashboard').remove();
	}
	if($('body #settings-menu').length > 0){
		$('body #settings-menu').remove();
	}
	if($('body #inbox-menu').length > 0){
		$('body #inbox-menu').remove();
	}
	if($('body #dashboard-menu').length > 0){
		$('body #dashboard-menu').remove();
	}
}

// resize function
function resize(){
	var width = $('body').outerWidth(true) - ($('#sidebar').outerWidth(true) + $('#iconbar').outerWidth(true));
	$('#filter-list').css('width', width+'px');
	$('#main').css('width', width+'px');
	
	var height = $('body').outerHeight(true) - ($('#filter-list').outerHeight(true) + $('#event-tabs').outerHeight(true));
	$('#main').css('height', height+'px');
	
	if($('.gallery').length > 0){
		var width = calculatePhotoWidth($('.gallery').innerWidth(), 5);
		$('.gallery li > a, .gallery li > a > *').css('height', width+'px');
		$('.gallery li > a, .gallery li > a > *').css('width', width+'px');
	}
}

// dynamically calculate the width of photos
function calculatePhotoWidth(containerWidth, n){
	var width = (containerWidth / n) - 31;
	if((containerWidth / n) - 31 < 120){
		width = calculatePhotoWidth(containerWidth, n-1);
	}
	return width;
}

/* LOADING FUNCTIONS */
function loadingFilters(){
	$('.loading').remove();
	$('#filter-list').replaceWith('<div id="filter-list">filters laden</div>');
}

function loadingUpdates(){
	$('.loading').remove();
	$('#messages').replaceWith('<div id="messages"><div class="loading"></div></div>');
}

function loadingStats(){
	$('.loading').remove();
	$('#event-stats').replaceWith('<div id="event-stats"><div class="loading"></div></div>');
}

function loadingMedia(){
	$('.loading').remove();
	$('#event-media').replaceWith('<div id="event-media"><div class="loading"></div></div>');
}

function loadingReports(){
	$('.loading').remove();
	$('#event-reports').replaceWith('<div id="event-reports"><div class="loading"></div></div>');
}