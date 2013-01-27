<?php

namespace models;

class Profile extends Saveable {
	
	protected $_id;
	protected $_username;
	protected $_fullname;
	protected $_profile_image;
	protected $_location;
	protected $_lang;
	protected $_created;
	protected $_klout;
	protected $_kred;
	
	protected $_table = 'profile';
	
	public function populate(){
		return true;
	}
	
	public static function __callStatic($method, $parameters){
		$parameters['obj'] = new self();
		return parent::__callStatic($method, $parameters);
	}
	
}