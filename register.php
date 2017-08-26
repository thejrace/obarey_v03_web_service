<?php
	
	require_once "init.php";

	$STATUS = "default";
	$SUCCESS = false;
	$TEXT = "";
	$ERROR = false;
	$OUTPUT = array();

	



	class Register {
		private $pdo, $user_id, $user_name, $return_text;

		public function __construct(){
			$this->pdo = DB::getInstance();
		}

		public function action( $input ){
			// salt olustur
			$salt = utf8_encode( mcrypt_create_iv( 64, MCRYPT_DEV_URANDOM ) );
			// PHP 5.1.2 ve sonrasinda var hash() fonksiyonu
			// sifre ve salti seviştir
			$hash = hash( 'sha256', $salt . $input["password"] );
			// @username
			$this->user_name = "@".substr( $input["email"], 0, strpos( $input["email"], "@" ) );
			if( !$this->pdo->insert( DBT_USERS, array(
				"email"        	=> $input["email"],
				"name"  	   	=> $this->user_name,
				"points" 		=> 0,
				"wins"			=> 0,
				"loses"		 	=> 0,
				"yerabos" 		=> 10,
				"register" 		=> Common::get_datetime(),
				"password" 		=> $hash,
				"salt" 			=> $salt
			)) ){
				$this->return_text = "Bir hata oluştu. Lütfen tekrar deneyin.";
				return false;
			}

			$this->user_id = $this->pdo->lastInsertedId();


			if( !$this->pdo->insert( DBT_USER_QUESTION_STATS, array(
				"user_id" => $this->user_id,
				"answered" => 0,
				"correct_count" => 0,
				"wrong_count" => 0
			))){
				$this->return_text = "Bir hata oluştu. Lütfen tekrar deneyin. LOG QUESTION STATS";
				return false;
			}

			$this->return_text = "Kayıdınız gerçekleşti.";
			return true;
		}

		public function get_user_id(){
			return $this->user_id;
		}

		public function get_user_name(){
			return $this->user_name;
		}

		public function get_return_text(){
			return $this->return_text;
		}
	}

	class Login {
		private $pdo, $return_text, $user_id, $user_data_bundle = array(), $check_result;

		public function __construct(){
			$this->pdo = DB::getInstance();
		}

		public function action($input){

			// kullanici datalari al dbden
			$check = $this->pdo->query("SELECT * FROM " . DBT_USERS . " WHERE id = ?", array( $input["user_id"] ) );
			if( $check->count() == 1 ){
				$user_data = $check->results();
				$user_salt = $user_data[0]["salt"];
				$user_pass = $user_data[0]["password"];
			} else {
				$this->return_text = "User_id gelmedi.";
				return false;
			}

			// sifre kontrolu
			$input_pass = hash( 'sha256', $user_salt . $input["password"] );
			if( $input_pass != $user_pass ){
				$this->return_text = "Şifre yanlış.";
				return false;
			}

			// dbyi guncelle
			if( !$this->pdo->update( DBT_USERS, "id", $input["user_id"], array(
				'last_connect' => Common::get_datetime()
			))) {
				$this->return_text = "Bir hata oluştu. Lütfen tekrar deneyin. Datetame";
				return false;
			}
			return true;
		}

		// start activity mail check
		public function check_email( $email ){
			if(filter_var( $email, FILTER_VALIDATE_EMAIL) === false){
				$this->return_text = "Eposta adresi geçersiz.";
				return false;
			}
			$check = $this->pdo->query("SELECT * FROM " . DBT_USERS . " WHERE email = ?", array($email));
			if( $check->count() == 1 ){
				foreach( $check->results() as $res ){
					$this->user_id = $res["id"];
					$this->check_result = 1;
				}
			} else {
				$this->check_result = 0;
			}
			return true;
		}

		public function get_check_result(){
			return $this->check_result;
		}

		public function get_user_id(){
			return $this->user_id;
		}

		public function get_return_text(){
			return $this->return_text;
		}
	}


	if( $_POST ){
		if( Input::get("type") != "" ){
			$DB = DB::getInstance();
			switch( Input::get("type") ){

				case 'do_login_action':
					$Login = new Login; 
					if( $Login->action( array( "user_id" => Input::get("user_id"), "password" => Input::get("password") ) ) ){
						$DataBundle = new UserDataBundle( Input::get("user_id") );
						if( $DataBundle->get_user_data() &&
							$DataBundle->get_stats() &&
							$DataBundle->get_friend_requests() &&
							$DataBundle->get_game_requests() &&
							$DataBundle->get_game_requests_sent() &&
							$DataBundle->get_friends() &&
							$DataBundle->get_finished_games() &&
							$DataBundle->get_games()
						) {
							$OUTPUT["user_bundle"] = $DataBundle->get_data();
						}
						$OUTPUT["game_rules"] =	$RULES;
					} else {
						$ERROR = true;
						$TEXT = $Login->get_return_text();
					}
					$SUCCESS = true;
				break;


				case 'do_register_action':
					$Register = new Register;
					if( $Register->action( array( "email" => Input::get("email"), "password" => Input::get("password") ) ) ){
						$OUTPUT["user_id"] = $Register->get_user_id();
						$OUTPUT["user_name"] = $Register->get_user_name();
						
						$OUTPUT["game_rules"] =	$RULES;
					} else {
						$ERROR = true;
						$TEXT = $Register->get_return_text();
					}
					$SUCCESS = true;
				break;

				case 'do_check_for_email':
					$Login = new Login;
					if( $Login->check_email( Input::get("email")) ){
						$STATUS = $Login->get_check_result();
						if( $STATUS == 1 ) $OUTPUT["user_id"] = $Login->get_user_id(); 
					} else {
						$ERROR = true;
						$TEXT = $Login->get_return_text();
					}
					$SUCCESS = true;
				break;
			}
		}
	}
	$OUTPUT["error"] = $ERROR;
	$OUTPUT["success"] = $SUCCESS;
	$OUTPUT["text"] = $TEXT;
	$OUTPUT["status"] = $STATUS;
	$OUTPUT["type"] = Input::get("type");
	//$OUTPUT["post"] = $_POST;
	echo json_encode($OUTPUT);
	// echo "<pre>";
	// print_r($OUTPUT);
?>


<fieldset>
<legend>Register</legend>
<form action="" method="post">
	<input type="text" name="type" placeholder="type "/>
	<input type="text" name="password"  placeholder="pass.."/>
	<input type="text" name="email" placeholder="email.." />
	<input type="submit" value="gonder" />
</form>
</fieldset>

<fieldset>
<legend>Login</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_login_action" />
	<input type="text" name="user_id"  placeholder="user_id.."/>
	<input type="text" name="password" placeholder="pass.." />
	<input type="submit" value="gonder" />
</form>
</fieldset>