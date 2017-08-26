<?php 

class DB {
	private static $_instance = null;
	private $_pdo,
			$_query,
			$_error = false,
			$_results,
			$_count = 0,
			$_lastInsertedId;

	private function __construct() {
		try {
			$this->_pdo = new PDO('mysql:host='.DB_IP.';dbname='.DB_NAME, DB_NAME, DB_PASS);
			$this->query("SET NAMES 'utf8'; SET CHARSET 'utf8'");
		} catch(PDOException $e) {
			die($e->getMessage());
		}
	}


	// aşağıdaki fonskiyonla consturct fonksiyonumuz kalıbını çıkartmış olduk.
	// instance = örnek demek
	// yani başka bir sayfada database'e tekrar tekrar bağlanmak yerine aldığımız kalıbı kullanıyoruz.
	// staticler zaten en hızlı ulaşılan metodlar

	public static function getInstance() {  // static fonskiyonlarda $this yerine ::self kullanılır.
		if(!isset(self::$_instance)) {
			self::$_instance = new DB();
		}
		return self::$_instance;
	}

	// DB::getInstance()->query("SELECT username FROM users WHERE username = ?", array('ahmet'));
	public function query($sql, $params = array() ) {
		// error'u resetledik. Boylece hata alirsak, o an yaptigimiz query'nin hatasini aliyoruz.
		// resetlemezsen önce yaptığın querylerden aldıgın errorları görebilirsin.
		$this->_error = false;
		// eger query'de bi sıkıntı yoksa, bind yapılacak değişken var mı yok mu bakacağız.
		// eğer varsa x adlı sayaç başlatıp x değerini bind yapılacak değere eşitliyoruz. index mantığı
		// Böylece bindValue(1, degisken) olayını otomatik yapmış oluyoruz.
		// ne kadar değişken varsa, o sayı x' e eşit olmuş oluyor.
		if( $this->_query = $this->_pdo->prepare($sql) ) {
			$x = 1;
			if( count($params) ) {
				foreach($params as $param) {
					$this->_query->bindValue($x, $param);
					$x++;
				}
			}
			// bind yapılacak değişken olup olmadığını kontrol ettikten sonra query'yi execute ediyoruz.
			if( $this->_query->execute() ) {
				//echo 'Success';
				$this->_results = $this->_query->fetchAll(PDO::FETCH_ASSOC);
				$this->_count = $this->_query->rowCount();
				
			} else {
				$this->_error = true;
			}

		}

		return $this;
	}
												    //DELETE FROM , WHERE KISMI -- 3 elemanlik array
	//örn index.php'de => $user = DB::getInstance->delete("users", array('username', '=', 'ahmet' ));
	public function action($action, $table, $where = array()) {
		// Where kismi dogru mu, ( field, operator, value )
		if(count($where) === 3) {
			$operators = array('=', '>', '<', '<=', '>=');

			//Where deki dataları değişkenlere aldık ve tanımlamış olduk.
			$field      = $where[0];
			$operator   = $where[1];
			$value 		= $where[2];

			//Operator $operators array'inde tanımlı mı? kontrol ettik.
			if(in_array($operator, $operators)) {

				//$sql = "SELECT * FROM users WHERE username = 'ahmet' ";
				//value kısmını query yaparken bind yapiyoruz. O yuzden burada ? olarak yaziyoruz
				$sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";

				//eğer sql->query 'de bir hata yoksa sonucu döndür
				if(!$this->query($sql, array($value))->error()) {
					return $this;
				}
			}

		}

		// Bağlantıyı sonlandır.
		$this->_pdo = NULL;

		// hata varsa dönme
		return false;
	}

	public function get($table, $where) {
		return $this->action('SELECT *', $table, $where);
	}

	public function delete($table, $where) {
		return $this->action('DELETE', $table, $where);
	}

	public function insert($table, $fields = array()){
		$keys = array_keys($fields);
		$values = '';
		$x = 1;
		//$sql = INSERT INTO users (`username`,`password`,`salt`); gibi gözükecek

		foreach($fields as $field) {
			$values .= '?';
			if($x < count($fields) ){
					//values = ?, ?, ?  -> bu hale geldi
				$values .= ', ';
			}
			$x++;
		}
			// INSERT INTO users (`username`,`password`,`salt`) VALUES (?, ?, ?) oldu 
		$sql = "INSERT INTO {$table} (`".implode('`,`', $keys)."`) VALUES ({$values})";

		if(!$this->query($sql, $fields)->error()) {

			$this->_lastInsertedId = $this->_pdo->lastInsertId();
			return true;
		}

		// Bağlantıyı sonlandır.
		$this->_pdo = NULL;
		return false;
	}

	public function update($table, $data, $dataVal, $fields) {
		$set = '';
		$x = 1;

		foreach($fields as $name => $values) {
			//password = ? olarak cikti alacagimiz parti
			$set .= "{$name} = ?";
			if($x < count($fields)) {
				$set .= ', ';
			}
			$x++;
		}
		//UPDATE users SET password = ?, name = ? WHERE id = 3 --> olarak cıktı aldık
		$sql = "UPDATE {$table} SET {$set} WHERE {$data} = {$dataVal}";
		//$sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";
		
		if(!$this->query($sql, $fields)->error()) {
			return true;
		}

		// Bağlantıyı sonlandır.
		$this->_pdo = NULL;
		return false;
	}

	public function get_auto_increment( $table ){
		$qu = $this->query("SHOW TABLE STATUS LIKE '{$table}' " )->results();
		return $qu[0]["Auto_increment"];
	}

	public function results(){
		//$this->_query = $this->_pdo->prepare($sql)
		//$this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
		return $this->_results;
	}

	public function first(){
		// ilk elemani fetch ettik
		return $this->_results()[0];
	}

	public function error() {
		// true veya false donecek
		return $this->_error;
	}

	public function count() {
		//$this->_count = $this->_query->rowCount();
		return $this->_count;
	}

	public function lastInsertedId(){
		return $this->_lastInsertedId;
	}

}