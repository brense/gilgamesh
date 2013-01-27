<?php
if(isset($this->_vars['event'])){
$event = $this->_vars['event'];
?>
<div class="event-details">
	<img src="images/<?php echo str_replace(' ', '', @array_shift(@explode(',', $event->event_type))); ?>.png" alt="<?php echo $event->event_type; ?>">
	<h2><?php echo ucfirst($event->name); ?></h2>
	<p><?php echo date('d/m/Y', $event->start); ?></p>
	<ul>
		<li>Piek: <strong><?php echo date('d/m/Y', $event->peak->timestamp); ?> om <?php echo date('H:i', $event->peak->timestamp); ?></strong></li>
		<li>Berichten in piek: <strong><?php echo $event->peak->messages; ?></strong></li>
		<li>Locatie: <strong><?php echo $event->location->name; ?></strong></li>
		<li>Meest gebruikte hashtags: <strong>
			<?php
				$n = 0;
				foreach($event->hashtags as $hashtag => $count){
					if($n > 0) echo ', ';	
			?><a href="" class="hashtag" rel="<?php echo $event->id; ?>">#<?php echo $hashtag; ?> <span><?php echo $count; ?></span></a><?php $n++; } ?>
		</strong></li>
	</ul>
	<div class="buttons">
		<a href="" rel="<?php echo $event->id; ?>" class="invite-guest">Gast uitnodigen</a>
	</div>
	<div class="messages"><?php echo $event->total_messages; ?></div>
</div>
<?php } else { echo 'Gebeurtenis niet gevonden'; } ?>