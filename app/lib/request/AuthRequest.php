<?php

namespace request;

class AuthRequest extends Request {
	public function sign(Array $options = array()){
		if(isset($options['username']) && isset($options['password'])){
			$this->_http_auth = $options['username'] . ':' . $options['password'];
		} 
	}
}