<ul id="settings_menu" class="menu">
	<li<?php if(!isset($this->_vars['tab']) || $this->_vars['tab'] == ''){ echo ' class="selected"'; } ?>><a href="instellingen/">Algemene instellingen</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'processes'){ echo ' class="selected"'; } ?>><a href="instellingen/processen/">Achtergrond processen beheren</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'sources'){ echo ' class="selected"'; } ?>><a href="instellingen/bronnen/">Bronnen beheren</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'events'){ echo ' class="selected"'; } ?>><a href="instellingen/gebeurtenissen/">Gebeurtenissen beheren</a></li>
	<li<?php if(isset($this->_vars['tab']) && $this->_vars['tab'] == 'filters'){ echo ' class="selected"'; } ?>><a href="instellingen/filters/">Filters beheren</a></li>
</ul>