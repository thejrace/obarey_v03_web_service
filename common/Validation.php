<?php

class Validation {

	protected $errorHandler,
			  $_rules = array('req', 'minlen', 'maxlen', 'email', 'matches', 'unique', 'unique_edit', 'numerik', 'pozNumerik', 'not_zero'),
			  $_hataMesajlari = array(
			  	'req' 	   => ':field boş bırakılamaz.',
			  	'minlen'   => ':field en az :rule_value karakter olmalıdır.',
			  	'maxlen'   => ':field en fazla :rule_value karakter olabilir',
			  	'email'    => 'Lütfen geçerli bir eposta adresi giriniz.',
			  	'matches'  => 'Sifreler uyusmuyor. :field ile :rule_value aynı olmalıdır.',
			  	'unique'   => ':field zaten kullanımda. Başka bir tane deneyin.',
			  	'unique_edit'   => ':field zaten kullanımda. Başka bir tane deneyin.',
			  	'numerik'  => 'Lütfen yalnızca rakam kullanın.',
			  	'pozNumerik' => ':field rakamlardan oluşmalı ve negatif değer olmamalıdır.',
			  	'not_zero'    => ':field sıfırdan büyük olmalıdır.'
			  ); 

	protected $_inputs;

	private $pdo;

	public function __construct(InputErrorHandler $errorHandler){
		// $this->pdo = $pdo;
		$this->pdo = DB::getInstance();
		$this->errorHandler = $errorHandler;
	}

	// Inputları ve kurallarını kontrol et ve validate kontrolü fonksiyonuna gönder.
	public function check($inputs, $rules ){

		// Input name'lerin oldugu array
		$this->_inputs = $inputs;

		foreach($inputs as $input => $value){
				// Input name'lerle, post edilen input name'lerin aynı olup olmadığını kontrol ediyoruz
			if(in_array($input, array_keys($rules))){
				$this->validate(array(
					'field' => $input,
					'value' => $value,
					'rules' => $rules[$input]  // $item' e göre onun rule ları.
				));
			}
		}
	}

	// Benim input list versiyonum
	public function check_v2( $inputs, $rules ){

		// matches de kullaniyorum
		$this->_inputs = $inputs;

		foreach( $inputs as $input => $value ){
			if(in_array($input, array_keys($rules))){
				$this->validate(array(
					'field' => $input,
					'value' => $value,
					'rules' => $rules[$input][0]
				));
			}	
		}
	}

	// Error gosterirken input adlarini guzel yazmak icin
	// Genel bir translate icin liste yapip bunlari oradan almak lazim
	protected function convert_field( $field ){
		$list = array(
			"product_name" 	=> "Ürün Adı",
			"price_1" 		=> "Fiyat 1",
			"price_2" 		=> "Fiyat 2",
			"price_3" 		=> "Fiyat 3",
			"stock_amount" 	=> "Stok Adedi",
			"stock_code" 	=> "Stok Kodu",
			"sale_percent" 	=> "İndirim Oranı",
			"category_id" 	=> "Kategori",
			"category_name" => "Kategori adı",
			"shipment_cost" => "Kargo Ücreti",
			"variant_name"  => "Varyant adı"
		);

		return isset( $list[$field] ) ? $list[$field] : $field;
	}

	// Asıl kontrolü yaptığımız fonksiyon
	protected function validate($input){
		
		$field = $input['field'];

		$field_pretty = $this->convert_field($field);
		
		// Rule yani kuralları aç ve en başta belirlediğimiz rules arrayine uyuyo mu kontrol et
		foreach($input['rules'] as $rule => $rule_value){
			
			if(in_array($rule, $this->_rules)){
				
				// Bu class'daki metodları buradaki rule adına göre otomatik
				// çalıştırıyor. Switch case' e falan gerek kalmadan
				// Eğer çalıştıramazsa error yazdır.
				if(!call_user_func_array([$this, $rule], [$input, $input['value'], $rule_value])){
					// unique_edit kontrolunde rule_value array olarak geldigi icin ( 0=>db_tablosu, 1 =>duzenlenen urun id si)
					// kontrol edip ilk keyi handler'a geciyorum
					if( is_array( $rule_value) )
						$rule_value = $rule_value[0];

					$this->errorHandler->addError(
						str_replace(array(':field', ':rule_value'), array($field_pretty, $rule_value), $this->_hataMesajlari[$rule]),
						$field
					);
				}
			}

		}
	}

	// Error varsa errorHandler metodlarından errorKontrol'u döndür. True veya false dönecek
	public function failed(){
		return $this->errorHandler->errorKontrol();
	}

	// Dışarıdan errorHandler'ı bu class üzerinden çalıştırmak için kullandığımız fonksiyon
	public function errors(){
		return $this->errorHandler;
	}

	protected function unique($input, $value, $rule_value){
		// unique_edit
		if(  $rule_value[1] != "" ) {
			$kontrol = $this->pdo->query("SELECT * FROM {$rule_value[0]} WHERE ( {$input['field']} = ? && id != ? )", array($value, $rule_value[1]));
			$sonuc = $kontrol->count();	
			// Session::set("hederoy", "editunk");
			return $sonuc != 0 ? false : true;
		} 
		// Session::set("hederoy", "add unik");
		$kontrol = $this->pdo->query("SELECT * FROM {$rule_value[0]} WHERE {$input['field']} = ?", array($value));
		$sonuc = $kontrol->count();		
		return $sonuc != 0 ? false : true;
	}

	// False döndügünde error yazdıramadigi icin, ajax error return de patliyor ama çalışıyor
	protected function unique_edit( $input, $value, $rule_value ){
		$kontrol = $this->pdo->query("SELECT * FROM {$rule_value[0]} WHERE ( {$input['field']} = ? && id != ? )", array($value, $rule_value[1]));
		$sonuc = $kontrol->count();		
		return $sonuc != 0 ? false : true;
	}

	protected function not_zero( $input, $value, $rule_value  ){
		return !( $value <= 0 );
	}

	protected function matches($input, $value, $rule_value){
		return $value === $this->_inputs[$rule_value];
	}

	// protected function escape($input, $value, $rule_value){
	// 	return htmlspecialchars($input, ENT_QUOTES, "UTF-8");
	// }

	protected function req($input, $value, $rule_value){
		$trim_value = trim($value);
		return !empty($trim_value);
	}

	protected function minlen($input, $value, $rule_value){
		return mb_strlen($value) >= $rule_value;
	}

	protected function maxlen($input, $value, $rule_value){
		return mb_strlen($value) <= $rule_value;
	}

	protected function numerik($input, $value, $rule_value){
		return is_numeric($value) ? true : false;
	}

	protected function pozNumerik($input, $value, $rule_value){
		return is_numeric($value) && $value >= 0;
	}

	protected function email($input, $value, $rule_value){
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

}


