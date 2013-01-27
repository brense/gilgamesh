<?php

namespace storage;

abstract class SearchEngine {
	
	protected $_handle;
	
	public function __construct(Array $config){
		
	}
	
	abstract public function query($index, $query);
	
}