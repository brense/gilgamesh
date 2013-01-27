// INITIALIZATION
// init global variables
var events = {};
var updates = {};
var profiles = {};
var views = {};
var selectedEvent = 0;
var map;
var heatmap;
var mc;
var lastLoadedMap = 0;
var lastLoadedStats = 0;
var lastLoadedMedia = 0;

// do the resizing
resize();
$(window).resize(function(e){
	resize();
});

// init map
if($('#map_canvas').length > 0){
	var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = "https://maps.googleapis.com/maps/api/js?sensor=false&libraries=visualization&callback=initializeMap";
	document.body.appendChild(script);
}

// init page
loadEventList();
loadFilterList();

// DELEGATES
$('body').delegate('#sidebar #event-list li', 'click', function(e){
	e.preventDefault();
	getEvent($(e.currentTarget).attr('id').replace('event-', ''));
});

$('body').delegate('#event-tabs a', 'click', function(e){
	e.preventDefault();
	selectTab($(e.currentTarget).attr('class'));
});

$('body').delegate('#filter-list li a', 'click', function(e){
	e.preventDefault();
	if(!$(e.currentTarget).hasClass('add-filter')){
		toggleFilter($(e.currentTarget));
	}
});

$('body').delegate('.show_user', 'click', function(e){
	e.preventDefault();
	userId = $(e.currentTarget).attr('rel');
	loadingMessages();
	$('#event-tabs li').removeClass('selected');
	$('#messages').show();
	$('#event-tabs a.messages').parent().addClass('selected');
	
	$.get('do/models/Update/user?id='+userId, function(response){
		$('#messages').replaceWith(response);
	});
});

// LIST VIEW FUNCTIONS
function loadFilterList(){
	$.get('do/views/FilterListView/render', function(response){
		$('#filter-list').replaceWith(response);
	}, 'html');
}

function loadEventList(){
	var view = 'EventListView';
	if(views[view] == null){
		$.get('do/views/'+view+'/render', function(response){
			$('#events').replaceWith(response);
			views[view] = response;
		}, 'html');
	} else {
		$('#events').replaceWith(views[view]);
	}
}

// LOADING FUNCTIONS
function loadingFilters(){
	$('#filter-list').replaceWith('<div id="filter-list">filters laden</div>');
}

function loadingMessages(){
	$('#messages').replaceWith('<div id="messages"><div class="loading"></div></div>');
}

function loadingStats(){
	$('#event-stats').replaceWith('<div id="event-stats"><div class="loading"></div></div>');
}

function loadingMedia(){
	$('#event-media').replaceWith('<div id="event-media"><div class="loading"></div></div>');
}

// LOAT TAB FUNCTIONS
function loadMessagesEvent(eventId){
	$('#event-stats').hide();
	$('#event-media').hide();
	
	loadingMessages();
	$('#messages').show();
	var view = 'MessageListView';
	if(events[eventId].views == null || events[eventId].views[view] == null){
		$.get('do/views/'+view+'/render', function(response){
			$('#messages').replaceWith(response);
			if(events[eventId].views == null){
					events[eventId].views = {};
				}
			events[eventId].views[view] = response;
		}, 'html');
	} else {
		$('#messages').replaceWith(events[eventId].views[view]);
	}
}

function loadMapEvent(eventId){
	$('#messages').hide();
	$('#event-stats').hide();
	$('#event-media').hide();
	
	if(eventId == null){
		eventId = 0;
	} else if(eventId == lastLoadedMap && events[eventId] != null && events[eventId].geo_data != null){
		return true;
	}
	lastLoadedMap = eventId;
	
	$('#main').append('<div class="loading"></div>');
	
	if(events[eventId] == null || events[eventId].geo_data == null){
		$.get('do/models/Event/geoData?event='+eventId, function(response){
			var event_data = response.event_data;
			if(response.geo_data == null){
				event_data.geo_data = [];
			} else {
				event_data.geo_data = response.geo_data;
			}
			events[event_data._id] = event_data;
			loadMapMarkers(events[event_data._id]);
			selectedEvent = event_data._id;
		}, 'json');
	} else {
		selectedEvent = eventId;
		loadMapMarkers(events[eventId]);
	}
}

function loadStatsEvent(eventId){
	$('#messages').hide();
	$('#event-media').hide();
	
	if(eventId == lastLoadedStats){
		$('#event-stats').show();
	} else {
		lastLoadedStats = eventId;
		loadingStats();
		$('#event-stats').show();
		var view = 'EventStatsView';
		if(events[eventId].views == null || events[eventId].views[view] == null){
			$.get('do/views/'+view+'/render', function(response){
				$('#event-stats').replaceWith(response);
				if(events[eventId].views == null){
					events[eventId].views = {};
				}
				events[eventId].views[view] = response;
			}, 'html');
		} else {
			$('#event-stats').replaceWith(events[eventId].views[view]);
		}
	}
}

function loadMediaEvent(eventId){
	$('#messages').hide();
	$('#event-stats').hide();
	
	if(eventId == lastLoadedMedia){
		$('#event-media').show();
	} else {
		lastLoadedMedia = eventId;
		loadingMedia();
		$('#event-media').show();
		var view = 'EventMediaView';
		if(events[eventId].views == null || events[eventId].views[view] == null){
			$.get('do/views/'+view+'/render', function(response){
				$('#event-media').replaceWith(response);
				if(events[eventId].views == null){
					events[eventId].views = {};
				}
				events[eventId].views[view] = response;
			}, 'html');
		} else {
			$('#event-media').replaceWith(events[eventId].views[view]);
		}
	}
}

// OTHER FUNCTIONS
function getEvent(id){
	$('#event-list li').removeClass('selected');
	selectedEvent = id;
	loadingFilters();
	$.get('do/models/Event/select/?id='+id, function(response){
		loadFilterList();
		selectTab($('#event-tabs li.selected a').attr('class'));
		$('#event-list #event-'+id).addClass('selected');
	}, 'json');
}

function selectTab(tab){
	$('#event-tabs li').removeClass('selected');
	switch(tab){
		case 'messages': loadMessagesEvent(selectedEvent); break;
		case 'map': loadMapEvent(selectedEvent); break;
		case 'media': loadMediaEvent(selectedEvent); break;
		case 'stats': loadStatsEvent(selectedEvent); break;
		case 'reports': loadReportsEvent(selectedEvent); break;
	}
	$('#event-tabs a.'+tab).parent().addClass('selected');
}

function toggleFilter(filter){
	$.get(filter.attr('href'), function(response){
		if(response.toggle == "deactivate"){
			filter.parent().removeClass('selected');
		} else {
			filter.parent().addClass('selected');
		}
		events[selectedEvent].views = null;
		events[selectedEvent].geo_data = null
		selectTab($('#event-tabs li.selected a').attr('class'));
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
	
	loadMapEvent();
}

function loadMapMarkers(event_data){
	var markers = [];
	var heatmapData = [];
	
	map.setCenter(new google.maps.LatLng(event_data.location.lat, event_data.location.long));

	// make markers for all the geo data
	for(var n = 0; n < event_data.geo_data.length; n++){
		var position = new google.maps.LatLng(event_data.geo_data[n][0], event_data.geo_data[n][1]);
		heatmapData.push(position);
		
		var marker = new google.maps.Marker({
			position: position
		});
		
		markers.push(marker);
		
		google.maps.event.addListener(markers[n], "click", function(e){
			showGeoMessages(e.latLng)
		});
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
	if(mc instanceof MarkerClusterer){
		mc.clearMarkers();
	}
	mc = new MarkerClusterer(map, markers, {maxZoom: 19, zoomOnClick: false, avarageCenter: true});
	
	if($('#main > .loading').length > 0){
		$('#main > .loading').remove();
	}
		
	google.maps.event.addListener(mc, "click", function(cluster){
		var lats = [];
		var longs = [];
		var markers = cluster.getMarkers();
		for(var n = 0; n < markers.length; n++){
			lats.push(markers[n].getPosition().lat());
			longs.push(markers[n].getPosition().lng());
		}
		lats.sort();
		longs.sort();
		var latlng = new google.maps.LatLng(lats[0], longs[0]);
		var latlng2 = new google.maps.LatLng(lats[lats.length-1], longs[longs.length-1]);
		showGeoMessages(latlng2, latlng);
	});
}

function showGeoMessages(latlng, latlng2){
	loadingMessages();
	$('#event-tabs li').removeClass('selected');
	$('#messages').show();
	$('#event-tabs a.messages').parent().addClass('selected');
	
	if(latlng2 == null){
		var url = 'do/models/Update/geo?lat='+latlng.lat()+'&long='+latlng.lng();
	} else {
		var url = 'do/models/Update/geo?startlat='+latlng.lat()+'&startlong='+latlng.lng()+'&endlat='+latlng2.lat()+'&endlong='+latlng2.lng();
	}
	$.get(url, function(response){
		$('#messages').replaceWith(response);
	});
}

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

function calculatePhotoWidth(containerWidth, n){
	var width = (containerWidth / n) - 31;
	if((containerWidth / n) - 31 < 120){
		width = calculatePhotoWidth(containerWidth, n-1);
	}
	return width;
}