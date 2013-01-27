<div id="event-stats">
	<div class="spacing">
		<h3>Berichten per uur</h3>
		<div id="timeline">
			<div id="timeline-chart"></div>
			<div id="timeline-controls"></div>
		</div>
	</div>
	<div class="spacing">
		<h3>10 Meest spraakzame personen</h3>
		<div id="source-stats"></div>
	</div>
	<script>
		// TODO: move this code to map.js
		head.ready(function(){
			var height = ($('body').outerHeight(true) - ($('#event-tabs').outerHeight(true) + $('#filter-list').outerHeight(true))) / 2 - 100;
			
			var timelineData = new google.visualization.DataTable();
			timelineData.addColumn('date', 'Datum');
			timelineData.addColumn('number', 'Originele berichten');
			timelineData.addColumn('number', 'Retweets');
			timelineData.addColumn('number', 'Antwoorden');
			<?php
			foreach($this->_vars['bpm'] as $date => $values){
				$jsdate = date('m/d/Y H:i:s', $date);
				echo "timelineData.addRow([new Date('" . $jsdate . "')," . $values['original'] . "," . $values['rt'] . "," . $values['reply'] . "]);\n";
			}
			?>
			
			var dashboard = new google.visualization.Dashboard(document.getElementById('timeline'));
			var control = new google.visualization.ControlWrapper({
				'controlType': 'ChartRangeFilter',
				'containerId': 'timeline-controls',
				'options': {
					'filterColumnIndex': 0,
					'ui': {
						'chartType': 'LineChart',
						'chartOptions': {
							'chartArea': {'width': '100%'},
							'colors': ['#007ed3', '#ef3965', '#7bc627', '#7fbee9'],
							'hAxis': {'baselineColor': 'none'},
							'height': 50
						},
						'chartView': {
							'columns': [0, 1, 2, 3]
						},
						'minRangeSize': 86400000
					}
				}
			});
			chart = new google.visualization.ChartWrapper({
				'chartType': 'ColumnChart',
				'containerId': 'timeline-chart',
				'options':	{
					'height': height,
					'backgroundColor': '#fff',
					'legend': {'position': 'in', 'textStyle': {'color': '#000', 'fontName': 'Segoe UI', 'fontSize': '13'}},
					'chartArea': {'left': 0, 'top': 20, 'width': '100%', 'height': '100%'},
					'colors': ['#007ed3', '#ef3965', '#7bc627', '#7fbee9'],
					'vAxis': {'textPosition': 'in', 'baselineColor': '#000', 'gridlines': {'color': '#7fbee9'}},
					'hAxis': {'textPosition': 'none', 'baselineColor': '#000', 'gridlines': {'color': '#fff'}},
					'isStacked': true
				},
				'view': {
					'columns': [
						{
							'calc': function(dataTable, rowIndex) {
								return dataTable.getFormattedValue(rowIndex, 0);
							},
							'type': 'string'
						}, 1, 2, 3]
					}
			});
			dashboard.bind(control, chart);
			dashboard.draw(timelineData);
			
			var sourceData = new google.visualization.DataTable();
			sourceData.addColumn('string', 'Gebruiker');
			sourceData.addColumn('number', 'Aantal berichten');
			<?php
			foreach($this->_vars['users'] as $k => $v){
				echo "sourceData.addRow([decodeURI('" . rawurlencode($k) . "')," . $v . "]);\n";
			}
			?>
			sourceViz = new google.visualization.PieChart(document.getElementById('source-stats'));
			var sourceOpts = {
				height: height,
				sliceVisibilityThreshold: 1/500,
				backgroundColor: '#fff',
				legend: {position: 'right', textStyle: {color: '#000', fontName: 'Segoe UI', fontSize: '13'}},
				chartArea: {left: 0, top: 10, width: '100%', height: '90%'},
				colors: ['#007ed3', '#ef3965', '#7bc627', '#7fbee9'],
				vAxis: {textPosition: 'none', baselineColor: '#5299d3', gridlines: {color: '#fff'}}
			};
			sourceViz.draw(sourceData, sourceOpts);
		});
	</script>
</div>