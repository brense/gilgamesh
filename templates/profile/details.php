<?php $user = $this->_vars['user']; ?>
<div id="profile-details">
	<h3><?php if(strlen($user->name) > 0){ echo $user->name; } else { echo $user->screen_name; } ?> <a href="show_user?id=<?php echo $user->id; ?>">@<?php echo $user->screen_name; ?></a></h3>
	<div class="profile">
		<img class="country-flag" src="images/flags/<?php echo $user->lang; ?>.png" alt="<?php echo $user->location; ?>" title="<?php echo $user->location; ?>">
		<span class="klout-score">k/i</span>
		<p><?php echo $user->description; ?></p>
	</div>
</div>