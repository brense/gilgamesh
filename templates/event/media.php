<?php // TODO: do not display all images at once, slice them and put them into javascript page objects ?>
<?php $urls = $this->_vars['urls']; ?>
<div id="event-media">
	<div class="spacing">
		<ul class="gallery">
			<?php foreach($urls as $url){ ?>
			<li><a href="<?php echo $url['large']; ?>"><?php if($url['type'] == 'image'){ ?><img src="image_cache.php?img=<?php echo $url['img']; ?>" alt="" /><?php } else if($url['type'] == 'video'){ ?><iframe width="100" height="100" src="<?php echo $url['video']; ?>" frameborder="0" allowfullscreen></iframe><?php } ?></a></li>
			<?php } ?>
		</ul>
	</div>
</div>
<script>resize();</script>