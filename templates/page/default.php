<!DOCTYPE HTML>
<html>
	<head>
		<base href="<?php echo $this->_vars['root']; ?>" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo $this->_vars['title']; ?></title>
		<link rel="stylesheet" type="text/css" href="css/<?php echo $this->_vars['style']; ?>.css" />
		<script src="js/head.js"></script>
		<script src="js/markercluster.js"></script>
		<script src="https://www.google.com/jsapi"></script>
		<script>
			google.load('visualization', '1.1', {packages: ['corechart', 'controls']});
		</script>
	</head>
	<body>
		<?php require_once('templates/iconbar.php'); ?>
		<div id="sidebar">
			<ul id="events">
				<li><div class="spacing">gebeurtenissen laden</div></li>
			</ul>
		</div>
		<?php $this->_vars['tab'] = 'map'; require_once('templates/event/tabs.php'); ?>
		<div id="filter-list">filters laden</div>
		<div id="main">
			<div id="map_canvas"></div>
			<div id="messages" style="display:none;"></div>
			<div id="event-media" style="display:none;"></div>
			<div id="event-stats" style="display:none;"></div>
			<div id="event-reports" style="display:none;"></div>
			
			<div style="visibility:hidden; clear:both;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </div>
		</div>
		<script>
			head.js({jquery: 'js/jquery.js'}, 'js/app.js');
		</script>	
	</body>
</html>