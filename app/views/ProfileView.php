<?php

namespace views;

use \models\Update as Update;
use \models\Profile as Profile;
use \models\Event as Event;
use \storage\Storage as Storage;
use \models\Config as Config;

class ProfileView extends AbstractView {
	
	protected $_template = 'profile/details';
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		if(isset($_GET['id'])){
			$couch = Storage::database('CouchDB', Config::$db['couchdb']);
			$couch->mapper->setObject(new Profile());
			$url = 'profiles/' . $_GET['id'];
			$profile = $couch->query('GET', $url, array(), 'map');
			
			if($profile instanceof Profile){
				$this->_vars['user'] = $profile;
			} else {
				$this->_vars['user'] = new Profile();
			}
		}
	}
	
}