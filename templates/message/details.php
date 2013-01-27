<?php $message = $this->_vars['message']; ?>
<div class="message" id="message-<?php echo str_replace('/', '-', $message->id); ?>">
	<img src="image_cache.php?img=<?php echo $message->profile->profile_image; ?>" class="profile-picture" alt="">
	<div class="profile">
		<span class="source <?php echo $message->type; ?>">&nbsp;</span>
		<span class="real-name"><?php if(strlen($message->profile->fullname) > 0){ echo $message->profile->fullname; } else { echo $message->profile->username; } ?></span>
		<a href="" rel="<?php echo $message->profile->id; ?>" class="show-user">@<?php echo $message->profile->username; ?></a>
		<img class="country-flag" src="images/flags/<?php echo $message->profile->lang; ?>.png" alt="<?php echo $message->profile->location; ?>" title="<?php echo $message->user->location; ?>">
		<span class="klout-score">
			<?php if(isset($message->profile->klout->topics) && is_array($message->profile->klout->topics)){
				foreach($message->profile->klout->topics as $topic){
					echo $topic->displayName;
				}
			} ?>
			<?php echo isset($message->profile->kred->influence) ? $message->profile->kred->influence : ''; ?>
			<?php echo isset($message->profile->kred->outreach) ? '/ ' . $message->profile->kred->outreach : ''; ?>
		</span>
	</div>
	<div class="message-meta"><span class="message-time" title="<?php echo date('d/m/Y H:i:s', $message->created); ?>"><?php echo $message->time_ago; ?> geleden</span></div>
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