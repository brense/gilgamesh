<?php

use models\Config as Config;

abstract class Routing {
	
	private static $_routes = array();
	private static $_requestUri;
	
	public static function addRoute($method, $route, $callback){
		self::$_routes[strtoupper($method)][] = array('route' => $route, 'callback' => $callback);
	}
	
	public static function resolveRoute(){
		$requestUri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
		$requestUri = str_replace(Config::$root_url, '', 'http://' . $_SERVER['HTTP_HOST'] . $requestUri);
		
		$uri = explode('/', $requestUri);
		for($i = 0; $i < count($uri); $i++){
			if(strlen($uri[$i]) == 0){
				unset($uri[$i]);
			}
		}
		$selected = array();
		foreach(self::$_routes[$_SERVER['REQUEST_METHOD']] as $route){
			$arr = explode('/', $route['route']);
			$n = 0;
			foreach($arr as $value){
				// determine if the route part matches the requested url part
				if(substr($value, 0, 1) == ':' || (isset($uri[$n]) && $uri[$n] == $value)){
					$selected['callback'] = $route['callback'];
					if(substr($value, 0, 1) == ':'){
						if(isset($uri[$n])){
							$selected['parameters'][substr($value, 1)] = $uri[$n];
						} else {
							$selected['parameters'][substr($value, 1)] = implode($uri);
						}
					}
				} else {
					$selected = array();
					break;
				}
				// handle remaining parts of the requested uri
				if(!isset($arr[$n+1]) && isset($selected['callback']) && isset($uri[$n+1])){
					if(isset($selected['parameters'][substr($value, 1)])){
						for($i = $n+1; $i < count($uri); $i++){
							$selected['parameters'][substr($value, 1)] .= '/' . $uri[$i];
						}
					}
					break 2;
				}
				$n++;
			}
			// break the loop is a suitable callback has been found
			if(isset($selected['callback'])){
				break;
			}
		}
		return $selected;
	}
	
}