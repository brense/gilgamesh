<div id="event-list">
	<ul>
		<?php foreach($this->_vars['events'] as $event){ ?>
		<li class="event<?php if($this->_vars['selected_event'] == @array_pop(@explode('/', $event->id)) && (!isset($this->_vars['open_monitor']) || $this->_vars['open_monitor'] == false)){ echo ' selected'; } ?>" id="event-<?php echo @array_pop(@explode('/', $event->id)); ?>">
			<?php
				$template = new \models\Template('event/details', array('event' => $event));
				echo $template->render();
				
				$template = new \models\Template('event/summary', array('event' => $event));
				echo $template->render();
			?>
		</li>
		<?php } ?>
	</ul>
</div>