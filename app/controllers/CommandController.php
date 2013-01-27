<?php

namespace controllers;

abstract class CommandController {
	
	public static function getCommand($command){
		$command = '\commands\\' . $command;
		return new $command();
	}
	
}