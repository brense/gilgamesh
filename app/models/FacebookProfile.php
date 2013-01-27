<?php

namespace models;

class FacebookProfile extends Profile {
	
	protected $_name;
	
	public function populate(){
		$this->_username = $this->_name;
		$this->_fullname = $this->_name;
	}
	
}