<?php $event = $this->_vars['event']; ?>
<div id="map_canvas" style="width:100px;height:100px;">Netwerk niet beschikbaar</div>
<script type="text/javascript">	
	function initialize() {
		var mapOptions = {
			zoom: 9,
			center: new google.maps.LatLng(<?php echo $event->location->lat; ?>, <?php echo $event->location->long; ?>),
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			zoomControlOptions: {
				position: google.maps.ControlPosition.RIGHT_TOP
			},
			panControlOptions: {
				position: google.maps.ControlPosition.RIGHT_TOP
			}
		};
		
		var map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
		var markers = [];
		var heatmapData = [];
		var infowindow = new google.maps.InfoWindow();
		
		<?php
		$n = 0;
		foreach($this->_vars['coordinates'] as $message){
		echo '
		var position = new google.maps.LatLng(' . $message->key[1] . ', ' . $message->key[2] . ');
		
		heatmapData.push(position);
		
		var marker' . $n . ' = new google.maps.Marker({
			position: position,
			//map: map,
			title: "' . $message->value . '"
		});
		
		google.maps.event.addListener(marker' . $n . ', "click", function() {
			infowindow.close();
			getMessageHTML(' . $message->key[1] . ', ' . $message->key[2] . ', infowindow, map, marker' . $n . ');
		});
		
		markers.push(marker' . $n . ');
		';
		$n++;
		}
		?>
		
		var heatmap = new google.maps.visualization.HeatmapLayer({
			data: heatmapData,
			radius: 50
		});
		heatmap.setMap(map);
		
		google.maps.event.addListener(map, "click", function() {
			infowindow.close();
		});
		
		var mc = new MarkerClusterer(map, markers, {maxZoom: 19, zoomOnClick: false});
		
		google.maps.event.addListener(mc, "click", function(cluster){
			var lats = [];
			var longs = [];
			var markers = cluster.getMarkers();
			for(var n = 0; n < markers.length; n++){
				lats.push(markers[n].getPosition().lat());
				longs.push(markers[n].getPosition().lng());
			}
			lats.sort;
			longs.sort;
			var startLat = lats[0];
			var endLat = lats[lats.length-1];
			var startLong = longs[0];
			var endLong = longs[longs.length-1];
			
			$.get('do/models/Update/geo?startlat='+startLat+'&startlong='+startLong+'&endlat='+endLat+'&endlong='+endLong, function(response){
				infowindow.setContent('<div style="width:400px;">'+response+'</div>');
				infowindow.setPosition(cluster.getCenter());
				infowindow.open(map);
			}, 'html');
		});
		
		function getMessageHTML(lat, long, infowindow, map, marker){
			$.get('do/models/Update/geo?lat='+lat+'&long='+long, function(response){
				infowindow.setContent('<div style="width:400px;">'+response+'</div>');
				infowindow.open(map,marker);
			}, 'html');
		}
	}
	
	var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = "https://maps.googleapis.com/maps/api/js?sensor=false&libraries=visualization&callback=initialize";
	document.body.appendChild(script);

</script>