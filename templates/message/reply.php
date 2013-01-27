<?php require_once('templates/message/details.php'); ?>
<form action="do/models/Update/<?php echo $this->_vars['state']; ?>/" method="post" id="reply-form">
	<fieldset>
		<textarea name="reply"><?php echo strip_tags(urldecode($this->_vars['msg'])); ?></textarea>
		<input type="hidden" name="id" value="<?php echo $this->_vars['id']; ?>" />
		<input type="hidden" name="original" value="<?php echo strip_tags(urldecode($this->_vars['msg'])); ?>" />
		<input type="submit" value="Verzenden" />
	</fieldset>
</form>