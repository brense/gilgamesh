<?php

namespace services\models;

use \models\Model as Model;

abstract class Update extends Model {
	
	public static function __callStatic($method, $parameters){
		switch(strtolower($method)){
			case 'twitter':
				return new TwitterUpdate();
				break;
			case 'facebook':
				return new FacebookUpdate();
				break;
		}
	}
	
}