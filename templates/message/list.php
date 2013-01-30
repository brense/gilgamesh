<?php if(!isset($this->_vars['append']) || !$this->_vars['append']){ ?>
<div id="messages">
	<div class="scroll-container">
		<?php }
			foreach($this->_vars['messages'] as $message){
				if(isset($this->_vars['no_update'])){
					$no_update = true;
				} else {
					$no_update = false;
				}
				$template = new \models\Template('message/details', array('message' => $message, 'no_update' => $no_update));
				echo $template->render();
			}
		?>
		<?php if(isset($this->_vars['next'])){ ?>
		<p class="more"><a href="?skip=<?php echo $this->_vars['next']; ?>&append=true" class="show-more"><span>Volgende weergeven</span></a></p>
		<?php } if(!isset($this->_vars['append']) || !$this->_vars['append']){ ?>
	</div>
</div>
<?php } ?>