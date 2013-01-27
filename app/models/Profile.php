<?php

namespace models;

class Profile extends Saveable {
	
	protected $_id;
	protected $_foreign_id;
	protected $_screen_name;
	protected $_name;
	protected $_profile_image_url;
	protected $_location;
	protected $_lang;
	protected $_created_at;
	protected $_friends_count;
	protected $_followers_count;
	protected $_messages_count;
	protected $_messages;
	protected $_description;
	protected $_klout;
	protected $_kred;
	
	protected $_table = 'profile';
	
	private static $_database = 'profiles';
	
	public static function __callStatic($method, $parameters){
		$parameters['db'] = self::$_database;
		$parameters['obj'] = new self();
		return parent::__callStatic($method, $parameters);
	}
	
}