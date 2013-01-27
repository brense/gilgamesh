<ul id="event-tabs">
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'map'){ echo ' class="selected"'; } ?>><a href="" class="map">Kaart</a></li>
	<li<?php if(!isset($this->_vars['tab']) || $this->_vars['tab'] == 'updates'){ echo ' class="selected"'; } ?>><a href="" class="updates">Berichten</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'media'){ echo ' class="selected"'; } ?>><a href="" class="media">Foto's/video's</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'stats'){ echo ' class="selected"'; } ?>><a href="" class="stats">Statistieken</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'reports'){ echo ' class="selected"'; } ?>><a href="" class="reports">Rapporten</a></li>
</ul>