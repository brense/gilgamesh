<?php $event = $this->_vars['event']; ?>
<div id="event-add">
	<form action="<?php echo (strlen((string)$event->id) > 0 ? 'do/models/Event/edit' : 'do/models/Event/add'); ?>" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend>Gebeurtenis <?php echo (strlen((string)$event->id) > 0 ? 'bewerken' : 'toevoegen'); ?></legend>
			<div class="uneven">
				<label>Type</label>
				<select name="type">
					<option value="">Kies een type</option>
					<option value="incident"<?php echo (stripos($event->event_type, 'incident') !== false ? ' selected="selected"' : ''); ?>>Incident</option>
					<option value="overlast"<?php echo (stripos($event->event_type, 'overlast') !== false ? ' selected="selected"' : ''); ?>>Overlast</option>
					<option value="werkzaamheden"<?php echo (stripos($event->event_type, 'werkzaamheden') !== false ? ' selected="selected"' : ''); ?>>Werkzaamheden</option>
					<option value="zoek opdracht"<?php echo (stripos($event->event_type, 'zoek opdracht') !== false ? ' selected="selected"' : ''); ?>>Zoek opdracht</option>
				</select>
			</div>
			<div><label>Sub type</label><input type="text" name="sub_type" value="<?php echo (strlen((string)$event->id) > 0 ? @array_pop(@explode(',', $event->event_type)) : ''); ?>" /></div>
			<div class="uneven"><label>Naam</label><input type="text" name="name" value="<?php echo (strlen((string)$event->id) > 0 ? $event->name : ''); ?>" /></div>
			<div><label>Hashtags</label><input type="text" name="hashtags" value="<?php if(strlen((string)$event->id) > 0) foreach($event->hashtags as $hashtag => $mc) echo $hashtag . ', '; ?>" /></div>
			<?php echo (strlen((string)$event->id) > 0 ? '<input type="hidden" name="id" value="' . $event->id . '" />' : ''); ?>
			<div class="uneven"><input type="submit" value="<?php echo ($event->id > 0 ? 'Bewerken' : 'Toevoegen'); ?>" /></div>
		</fieldset>
	</form>
</div>