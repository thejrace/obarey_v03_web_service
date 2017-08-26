<?php

class Session {

	public static function set($name, $value){
		return $_SESSION[$name] = $value;
	}


	public static function exists($name){
		return isset($_SESSION[$name]) ? true : false;
	}

	public static function destroy($name){
		if(self::exists($name)){
			unset($_SESSION[$name]);
		}
	}

	public function start(){
		if( !isset($_SESSION) ){
			session_start();
		}
	}

	public static function get($name, $key = null){
		
		// TOPARLA 
		if(isset($key)){

			if(isset($key[1])){

				if(isset($key[2])){
					return $_SESSION[$name][$key[0]][$key[1]][$key[2]];
				} else {
					return $_SESSION[$name][$key[0]][$key[1]];
				}
		
			} else {
				return $_SESSION[$name][$key[0]];
			}



			
		} else {
			if( self::exists($name) ){
				return $_SESSION[$name];
			} else {
				return "";
			}
			
		}	
	}

}