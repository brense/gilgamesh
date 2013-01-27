<?php if(isset($this->_vars['event'])){ $event = $this->_vars['event']; } else { $event = new \models\Event(); } ?>
<div id="event-add">
	<form action="<?php echo ($event->id > 0 ? 'do/event/edit' : 'do/event/add'); ?>" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend>Gebeurtenis <?php echo ($event->id > 0 ? 'bewerken' : 'toevoegen'); ?></legend>
			<div class="uneven">
				<label>Type</label>
				<select name="type">
					<option value="">Kies een type</option>
					<option value="incident"<?php echo ($event->type == 'incident' ? ' selected="selected"' : ''); ?>>Incident</option>
					<option value="overlast"<?php echo ($event->type == 'overlast' ? ' selected="selected"' : ''); ?>>Overlast</option>
					<option value="werkzaamheden"<?php echo ($event->type == 'werkzaamheden' ? ' selected="selected"' : ''); ?>>Werkzaamheden</option>
					<option value="zoek opdracht"<?php echo ($event->type == 'zoek opdracht' ? ' selected="selected"' : ''); ?>>Zoek opdracht</option>
				</select>
			</div>
			<div><label>Sub type</label><input type="text" name="sub_type" value="<?php echo ($event->id > 0 ? $event->sub_type : ''); ?>" /></div>
			<div class="uneven"><label>Naam</label><input type="text" name="name" value="<?php echo ($event->id > 0 ? $event->name : ''); ?>" /></div>
			<div><label>Importeer berichten uit csv</label><input type="file" name="csv" /></div>
			<?php echo ($event->id > 0 ? '<input type="hidden" name="id" value="' . $event->id . '" />' : ''); ?>
			<div class="uneven"><input type="submit" value="<?php echo ($event->id > 0 ? 'Bewerken' : 'Toevoegen'); ?>" /></div>
		</fieldset>
	</form>
</div>