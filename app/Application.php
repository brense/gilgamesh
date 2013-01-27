<?php

use models\Config as Config;

class Application {
	
	public function __construct($config, Array $options = array()){
		require_once('models' . DIRECTORY_SEPARATOR . 'Model.php');
		require_once('models' . DIRECTORY_SEPARATOR . 'Config.php');
		require_once('Log.php');
		
		// set the exception and error handler
		set_exception_handler(array($this, 'handleExceptions'));
		set_error_handler(array($this, 'handleErrors'));
		
		// init Config object, execute the config variables loading function and set application options
		$config();
		Config::$app = $this;
		foreach($options as $key => $value){
			Config::$$key = $value;
		}
		
		// start a session
		if(Config::$start_session === true){
			session_start();
		}
		
		// set file root
		if(strlen(Config::$bootstrap) > 0){
			Config::$file_root = str_replace(Config::$bootstrap, '', @array_shift(@get_included_files()));
		} else {
			$file_root = explode(DIRECTORY_SEPARATOR, @array_shift(@get_included_files()));
			array_pop($file_root);
			Config::$file_root = implode(DIRECTORY_SEPARATOR, $file_root) . DIRECTORY_SEPARATOR;
		}
		// set app path
		$src_path = explode(DIRECTORY_SEPARATOR, __FILE__);
		array_pop($src_path);
		Config::$src_path = implode(DIRECTORY_SEPARATOR, $src_path) . DIRECTORY_SEPARATOR;
		// set root url
		if(isset($_SERVER['HTTP_HOST'])){
			$root_url = explode('/', $_SERVER['SCRIPT_NAME']);
			array_pop($root_url);
			Config::$root_url = 'http://' . $_SERVER['HTTP_HOST'] . implode('/', $root_url) . '/';
		}
		
		// register class sources
		if(count(Config::$sources) == 0){
			$this->source('CUSTOM_PATH', Config::$file_root . 'custom' . DIRECTORY_SEPARATOR);
			$this->source('APP_PATH', Config::$src_path);
			$this->source('LIB_PATH', Config::$src_path . 'lib' . DIRECTORY_SEPARATOR);
		} else {
			foreach(Config::$sources as $name => $source){
				$this->source($name, $source);
			}
		}
		
		// set the autoloader
		if(count(Config::$autoloader) == 0){
			spl_autoload_register(array($this, 'autoload'));
		} else {
			$autoloader = Config::$autoloader;
			if(isset($autoloader['class'], $autoloader['function'])){
				spl_autoload_register(array($autoloader['class'], $autoloader['function']));
			} else {
				throw new Exception('invalid autoloader');
			}
		}
	}
	
	public function __call($method, $parameters){
		switch($method){
			// register class sources
			case 'source':
				if(count($parameters) == 1){
					return Config::$sources[$parameters[0]];
				} else if(count($parameters) == 2){
					Config::$sources[$parameters[0]] = $parameters[1];
				} else {
					throw new Exception('wrong number of arguments for "source"');
				}
				break;
			// register options
			case 'options':
				if(count($parameters) == 1){
					return Config::${$parameters[0]};
				} else if(count($parameters) == 2){
					Config::${$parameters[0]} = $parameters[1];
				} else {
					throw new Exception('wrong number of arguments for "options"');
				}
				break;
			// register http routes
			case 'get':
			case 'post':
			case 'put':
				if(isset($parameters[0]) && isset($parameters[1])){
					Routing::addRoute($method, $parameters[0], $parameters[1]);
				} else {
					throw new Exception('wrong number of arguments for "' . $method . '"');
				}
				break;
		}
	}
	
	public function handleRequest(){
		// resolve the route and execute the appropriate callback
		$route = Routing::resolveRoute();
		call_user_func_array($route['callback'], $route['parameters']);
	}
	
	private function autoload($class){
		// create a clean class path
		$path = str_replace('\\', '/', $class);
		// loop through class sources
		$found = false;
		foreach(Config::$sources as $source){
			if(file_exists($source . $path . '.php')){
				require_once($source . $path . '.php');
				spl_autoload($class);
				$found = true;
				break;
			}
		}
		// throw exception if class cannot be found
		if(!$found){
			throw new Exception('class ' . $class . ' not found');
		}
	}
	
	public function handleExceptions(Exception $exception) {
		if(Config::$debug){
			echo '<pre>';
			print_r($exception);
			echo '</pre>';
		} else {
			echo 'something went wrong';
			Log::write('Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString());
		}
		exit;
	}
	
	public function handleErrors($errno, $errstr, $error_file = null, $error_line = null, Array $error_context = null) {
		if(Config::$debug){
			$error = array(
				'no' => $errno,
				'error' => $errstr,
				'file' => $error_file,
				'line' => $error_line,
				'context' => $error_context
			);
			echo '<pre>';
			print_r($error);
			echo '</pre>';
		} else {
			Log::write('Error: no: ' . $errno . ', error: ' . $errstr, $error_file, $error_line, json_encode($error_context));
		}
	}
	
}