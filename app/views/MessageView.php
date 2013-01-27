<?php

namespace views;

use \models\Update as Update;
use \models\Profile as Profile;
use \models\Config as Config;
use \storage\Storage as Storage;

class MessageView extends AbstractView {
	
	protected $_template = 'message/reply';
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		if(isset($_GET['id'])){
			$update_id = $_GET['id'];
			
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode($update_id);
			$update = $couch->query($obj, 'map');
			
			$this->_vars['message'] = $update;
			
			if(isset($_GET['state'])){
				$this->_vars['state'] = $_GET['state'];
				$this->_vars['id'] = $update->id;
				switch($this->_vars['state']){
					case 'reply':
						$this->_vars['msg'] = urlencode('@' . $update->profile->username . ' ');
						break;
					case 'retweet':
						$this->_vars['msg'] = urlencode('RT ' . $update->text);
						break;
				}
			}
		}
	}
	
}