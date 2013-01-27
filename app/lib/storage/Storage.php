<?php

namespace storage;

use \models\Config as Config;

class Storage {
	
	// contains a list of singleton objects (e.g. database resources)
	private static $_instances = array();
	
	private function __construct($mode, $type, $config = null){
		switch($mode){
			case 'db':
				if(file_exists(str_replace('Storage.php', '', __FILE__) . $type . '.php')){
					if(!$config && isset(Config::$db[strtolower($type)])){
						$config = Config::$db[strtolower($type)];
					}
					if(!$config){
						throw new \Exception('No valid database configuration found');
					}
					
					$class = 'storage\\' . Config::$search->type;
					$config['search_engine'] = new $class((array)Config::$search);
					
					$class = 'storage\\' . $type;
					self::$_instances['db'][$type] = new $class($config);
				} else {
					throw new \Exception('Database type not supported');
				}
				break;
			default:
				throw new \Exception('Storage mode not supported');
				break;
		}
	}
	
	public static function remove($mode, $type){
		unset(self::$_instances[$mode][$type]);
	}
	
	public static function database($type, $config){
		if(!isset(self::$_instances['db'][$type])){
			new self('db', $type, $config);
		}
		return self::$_instances['db'][$type];
	}
	
}