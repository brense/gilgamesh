<div id="iconbar">
	<ul>
		<li <?php if($this->_vars['page'] == 'dashboard'){ echo ' class="selected"'; }?>>
			<a href=""><img src="images/profile.png" alt="" /></a>
		</li>
		<li <?php if($this->_vars['page'] == '' ||
			strpos($this->_vars['page'], 'monitor') === 0 ||
			strpos($this->_vars['page'], 'events') === 0 ||
			strpos($this->_vars['page'], 'statistieken') === 0 ||
			strpos($this->_vars['page'], 'map') === 0){ echo ' class="selected"'; }?>>
			<a href="monitor/"><img src="images/eye.png" alt="" /></a>
		</li>
		<li <?php if(strpos($this->_vars['page'], 'instellingen') === 0){ echo ' class="selected"'; }?>>
			<a href="instellingen/"><img src="images/settings.png" alt="" /></a>
		</li>
		<li <?php if(strpos($this->_vars['page'], 'berichten') === 0){ echo ' class="selected"'; }?>>
			<a href="berichten/"><img src="images/mail.png" alt="" /><span>3</span></a>
		</li>
	</ul>
</div>