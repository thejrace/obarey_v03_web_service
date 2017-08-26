<?php

class Input {
	public static function exists($type='post') {
		switch($type) {
			case 'post':
				return (!empty($_POST)) ? true : false;
			break;

			case 'get':
				return (!empty($_GET)) ? true : false;
			break;

			default:
				return false;
			break;
		}
	}

	public static function escape($input) {

		if( is_array($input) ){
			$escaped = array();
			foreach ( $input as $i => $val ){
				// array seklinde gelen post inputlar icin
				// ilk versiyonda patliyordu
				if( is_array($val) ){
					// arrayin tum elemanlarini temizledikten sonra
					// kaydetme islemi yapiyoruz
					foreach( $val as $item => $item_val ){
						$val[$item] = htmlspecialchars($item_val, ENT_QUOTES, "UTF-8");
					}
					// array ayni array, elemanlar temizlendi
					$escaped[$i] = $val;
				} else{
					$escaped[$i] = htmlspecialchars($val, ENT_QUOTES, "UTF-8");
				}
				
				
			}
			return $escaped;
		} else {
			return htmlspecialchars($input, ENT_QUOTES, "UTF-8");
		}
		
	}



	public static function get($input) {
		if(isset($_POST[$input]) ) {
			return Input::escape($_POST[$input]);
		} else if(isset($_GET[$input]) ) {
			return Input::escape($_GET[$input]);
		}
		// Yoksa input boş dön.
		return '';
	}

}