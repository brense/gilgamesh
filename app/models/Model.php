<?php

namespace models;

abstract class Model {
	
	public function __get($property){
		if(property_exists($this, '_' . $property)){
			return $this->{'_' . $property};
		}
	}
	
	public function __set($property, $value){
		if(property_exists($this, '_' . $property)){
			$this->{'_' . $property} = $value;
		}
	}
	
}