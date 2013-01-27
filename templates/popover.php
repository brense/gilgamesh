<div id="popover">
	<div class="popover_background">&nbsp;</div>
	<div class="popover_container">
		<div class="popover_header"><a href="" class="close_popover">sluiten</a></div>
		<div class="popover_content"></div>
	</div>
</div>
<script>
	$('#popover').bind('click', function(e){
		if($(e.target).attr('class') == 'popover_background'){
			$('#popover').remove();
		}
	});
	$('.close_popover').bind('click', function(e){
		e.preventDefault();
		$('#popover').remove();
	});
</script>