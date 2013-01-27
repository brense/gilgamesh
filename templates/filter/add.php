<form id="add-filter" action="do/models/Filter/add" method="post">
	<fieldset>
		<legend>Filter toevoegen</legend>
		<div class="uneven">
			<label>Type</label>
			<select name="field">
				<option value="">Kies een type</option>
				<option value="text">Woord</option>
				<option value="entities.hashtags.text">Hashtag</option>
				<option value="filter.imago">Positief imago</option>
				<option value="filter.worries">Bezorgdheid</option>
				<option value="filter.schade">Schade en slachtoffers</option>
				<option value="filter.info">Informatiebehoefte</option>
				<option value="filter.need">Gevraagde hulp</option>
				<option value="filter.offer">Aangeboden hulp</option>
				<option value="filter.period">Periode</option>
				<option value="user.screen_name">Persoon</option>
			</select>
		</div>
		<div><label>Waarde</label><input type="text" name="value" /></div>
		<input type="hidden" name="event_id" value="<?php echo $this->_vars['event']; ?>" />
		<div class="uneven"><input type="submit" value="Toevoegen" /></div>
	</fieldset>
</form>