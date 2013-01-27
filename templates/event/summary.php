<?php
if(isset($this->_vars['event'])){
$event = $this->_vars['event'];
?>
<div class="event-summary">
	<img src="images/<?php echo str_replace(' ', '', @array_shift(@explode(',', $event->event_type))); ?>.png" alt="<?php echo $event->event_type; ?>">
	<h4><?php echo ucfirst($event->getDisplayName()); ?></h4>
	<p><?php echo date('d/m/Y', $event->start); ?></p>
	<p>
		<?php $n = 0; foreach($event->hashtags as $hashtag => $count){
			if($n < 3){ ?>
		<a href="" class="hashtag" rel="<?php echo $event->id; ?>">#<?php echo $hashtag; ?> <span><?php echo $count; ?></span></a>
		<?php } if($n < 2){ echo ', '; } $n++; } ?>
	</p>
	<div class="messages"><?php echo $event->total_messages; ?></div>
</div>
<?php } ?>