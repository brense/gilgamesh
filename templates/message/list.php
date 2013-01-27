<?php if(!isset($this->_vars['append']) || !$this->_vars['append']){ ?>
<div id="messages"<?php if(isset($this->_vars['size']) && $this->_vars['size'] == 'small'){ echo ' class="small"'; } ?>>
	<div class="scroll-container">
		<?php }
			foreach($this->_vars['messages'] as $message){
				$template = new \models\Template('message/details', array('message' => $message));
				echo $template->render();
			}
		?>
		<?php if(isset($this->_vars['next'])){ ?>
		<p class="more"><a href="?skip=<?php echo $this->_vars['next']; ?>&append=true" class="show-more"><span>Volgende weergeven</span></a></p>
		<?php } if(!isset($this->_vars['append']) || !$this->_vars['append']){ ?>
	</div>
</div>
<?php } ?>