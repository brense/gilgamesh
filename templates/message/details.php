<?php $message = $this->_vars['message']; ?>
<div class="message" id="message-<?php echo str_replace('/', '-', $message->id); ?>">
	<img src="image_cache.php?img=<?php echo $message->user->profile_image_url; ?>" class="profile-picture" alt="">
	<div class="profile">
		<span class="source <?php echo $message->type; ?>">&nbsp;</span>
		<span class="real-name"><?php if(strlen($message->user->name) > 0){ echo $message->user->name; } else { echo $message->user->screen_name; } ?></span>
		<a href="" rel="<?php echo $message->user->id; ?>" class="show-user">@<?php echo $message->user->screen_name; ?></a>
		<img class="country-flag" src="images/flags/<?php echo $message->user->lang; ?>.png" alt="<?php echo $message->user->location; ?>" title="<?php echo $message->user->location; ?>">
		<span class="klout-score">
			<?php if(isset($message->user->klout->topics) && is_array($message->user->klout->topics)){
				foreach($message->user->klout->topics as $topic){
					echo $topic->displayName;
				}
			} ?>
			<?php echo isset($message->user->kred->influence) ? $message->user->kred->influence : ''; ?>
			<?php echo isset($message->user->kred->outreach) ? '/ ' . $message->user->kred->outreach : ''; ?>
		</span>
	</div>
	<div class="message-meta"><span class="message-time" title="<?php echo date('d/m/Y H:i:s', $message->created_at); ?>"><?php echo $message->time_ago; ?> geleden</span></div>
	<p class="message-body"><?php echo $message->text; ?></p>
	<div class="message-actions">
		<span class="meta sentiment">s</span>
		<span class="meta betrouwbaarheid">b</span>
		<a href="?state=reply&id=<?php echo $message->id; ?>" class="meta reply"><span class="reply">&nbsp;</span></a>
		<a href="?state=retweet&id=<?php echo $message->id; ?>" class="meta retweet"><span class="retweet<?php if($message->rt == 1){ echo ' active'; } ?>">&nbsp;</span></a>
		<a href="" rel="<?php echo $message->id; ?>" class="meta favorite"><span class="favorite<?php if($message->fav == 1){ echo ' active'; } ?>">&nbsp;</span></a>
		<a href="" rel="<?php echo $message->id; ?>" class="meta assign"><span class="assign">&nbsp;</span> toewijzen</a>
		<a href="" rel="<?php echo $message->id; ?>" class="meta flag<?php if($message->flag == 1){ echo ' active'; } ?>"><span class="flag">&nbsp;</span> vlaggen</a>
		<a href="" rel="<?php echo $message->id; ?>" class="meta read<?php if($message->read == 1){ echo ' active'; } ?>"><span class="read">&nbsp;</span> gelezen</a>
	</div>
</div>