<?php

namespace service\models;

abstract class Profile {
	
	public static function __callStatic($method, $parameters){
		switch(strtolower($method)){
			case 'twitter':
				return new TwitterProfile();
				break;
			case 'facebook':
				return new FacebookProfile();
				break;
		}
	}
	
}