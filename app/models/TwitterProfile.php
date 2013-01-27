<?php

namespace models;

class TwitterProfile extends Profile {
	
	protected $_name;
	protected $_screen_name;
	protected $_profile_image_url;
	protected $_created_at;
	protected $_friends_count;
	protected $_followers_count;
	protected $_messages_count;
	protected $_messages;
	protected $_description;
	
	public function populate(){
		$this->_fullname = $this->_name;
		$this->_username = $this->_screen_name;
		$this->_profile_image = $this->_profile_image_url;
		$this->_created = $this->_created_at;
	}
	
}