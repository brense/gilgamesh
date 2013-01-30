<div id="settings" class="full">
	<div class="main">
		<div class="spacing">
			<h3>Algemene instellingen</h3>
		</div>
	</div>
	<div class="processes" style="display:none;">
		<div class="spacing">
			<h3>Achtergrond processen beheren</h3>
		</div>
	</div>
	<div class="sources" style="display:none;">
		<div class="spacing">
			<h3>Bronnen beheren</h3>
		</div>
	</div>
	<div class="events" style="display:none;">
		<div class="spacing">
			<h3>Gebeurtenissen beheren</h3>
		</div>
		<table>
			<thead>
				<tr>
					<th>Gebeurtenis:</th>
					<th>Hashtags:</th>
					<th colspan="1">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php $i = 0; foreach($this->_vars['events'] as $event){ ?>
				<tr<?php if($i % 2) echo ' class="even"'; ?>>
					<td><?php echo $event->name; ?></td>
					<td><?php $n = 0; foreach($event->hashtags as $k => $v){ if($n > 0){ echo ', '; } echo '#' . $k; $n++; } ?></td>
					<td><a href="do/views/EventEditView/render?id=<?php echo rawurlencode($event->id); ?>" class="popover">bewerken</a></td>
				</tr>
				<?php $i++; } ?>
			</tbody>
		</table>
	</div>
	<div class="filters" style="display:none;">
		<div class="spacing">
			<h3>Filters beheren</h3>
		</div>
	</div>
</div>