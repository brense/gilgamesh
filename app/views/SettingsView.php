<?php

namespace views;

use \models\Config as Config;
use \background_process\BackgroundProcess as BackgroundProcess;
use \storage\Storage as Storage;

class SettingsView extends AbstractView {
	
	protected $_template = 'settings/main';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		// get a list of background processes
		$processes = array();
		$path = Config::$file_root . 'processes';
		if($handle = opendir($path)){
			while(($entry = readdir($handle)) !== false){
				if(!is_dir($path . DIRECTORY_SEPARATOR . $entry)){
					$processes[] = new BackgroundProcess($entry, Config::$file_root);
				}
			}
			closedir($handle);
		}
		$this->_vars['processes'] = $processes;
		
		// get a list of events
		$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
		$processes = array();
		$obj = new \StdClass();
		$obj->method = 'GET';
		$obj->url = '_design/event/_view/all?include_docs=true&reduce=false';
		$events = $db->query($obj, 'map');
		$this->_vars['events'] = $events;
	}
	
}