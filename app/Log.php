<?php

use models\Config as Config;

abstract class Log {
	
	public static function write($message, $file, $line, $trace){
		if(!file_exists(Config::$log_path)){
			mkdir(Config::$log_path);
		}
		$handle = fopen(Config::$log_path . date('Y-m-d') . '.log', 'a');
		fwrite($handle, time() . ';' . $message . ';' . str_replace("\n", '', $trace) . ';' . $file . '[' . $line . ']' . "\n");
		fclose($handle);
	}
	
}