<?php

class InputErrorHandler {


	protected $_errors = [];
	//public $json;


	// $error mesaj
	// $key hatanın eklendiği key 
	// Eğer key varsa onun arrayine hatayi ekle
	// Yoksa direk errors arrayina ekle hatayı.
	public function addError($error, $key = null){
		if($key){
			$this->_errors[$key][] = $error;
		} else {
			$this->_errors[] = $error;
		}
	}

	public function hataListele(){
		$html = "";
		foreach ( $this->_errors as $key => $val ){
			foreach( $val as $error ){
				$html .= '<div>' . $error . '</div>';
			}
			
		}
		return $html;
		// return $this->_errors;
	}

	// Sıfırdan yeni bir array
	// @id => varyant ürünü duzenlemede tabloda input idlerin sonunda varyantin id si var
	// o yuzden direk input adini gonderince( price_1 ) olmuyor. onun yerine sonuna id yi ekliyorum ( price_1_[ID] )
	public function js_format( $id = null ){
		$js = array();
		$item_id = '';
		if( isset( $id ) && $id != "" ) $item_id = '_'.$id;	
		foreach( $this->_errors as $error => $val ){
			$js[$error.$item_id] = $val[0];
		}

		return $js;
	}

	// Referansli yavas oluyomus
	public function js_format_ref( &$js ){
		$js = array();
		foreach( $this->_errors as $error => $val ){
			$js[$error] = $val[0];
		}
	}

	// post output icin non ajax
	public function js_string_format(){
		$h = "";
		foreach( $this->all() as $error ){
			foreach( $error as $key => $val ){
				$h .= $val ."<br>";
			}
		}
		return $h;
	}

	public function all(){
		return $this->_errors;
	}

	public function keyAll($key = null){
		return isset($this->_errors[$key]) ? $this->_errors[$key] : null;
	}

	public function errorKontrol(){
		return count($this->all()) ? true : false;
	}

}