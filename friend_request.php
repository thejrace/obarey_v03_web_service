<?php
	
	require_once "init.php";

	$STATUS = "default";
	$SUCCESS = false;
	$TEXT = "";
	$OUTPUT = array();


	class FriendAction {

		private $pdo, $friend_id, $user_id, $return_text;
		public function __construct( $user_id, $fid ){
			$this->pdo = DB::getInstance();
			$this->friend_id = $fid;
			$this->user_id = $user_id;
		}

		// query(delete) return yapmiyor bosuna return text yapiyorum..
		// boyle yapinca da hata vermiyor o yuzden query(select) ile kontrol etmeye gerek yok silme isleminden once
		public function delete(){
			// iki kombinasyonu da deniyoruz
			$this->pdo->query("DELETE FROM " . DBT_FRIENDS . " WHERE friend_1_id = ? && friend_2_id = ? ", array( $this->user_id, $this->friend_id ) );
			$this->pdo->query("DELETE FROM " . DBT_FRIENDS . " WHERE friend_1_id = ? && friend_2_id = ? ", array( $this->friend_id, $this->user_id ) );
			/*
			if( !$this->pdo->query("DELETE FROM " . DBT_USER_GAMES . " WHERE user_id = ? && opponents_id = ? ", array( $this->user_id, $this->friend_id ) ) ){
				if( !$this->pdo->query("DELETE FROM " . DBT_USER_GAMES . " WHERE user_id = ? && opponents_id = ? ", array( $this->friend_id, $this->user_id ) ) ){
					$this->return_text = "Aktif oyunlari yok.";
					return false;
				}
			}*/
			return true;
		}
		public function get_return_text(){
			return $this->return_text;
		}
	}

	if( $_POST ){
		if( Input::get("type") != "" ){

			if( Input::get("user_id") != "" && Input::get("friend_id") !=  "" ){
				$Request = new FriendRequest( Input::get("user_id"), Input::get("friend_id") );
			}

			switch( Input::get("type") ){


				case 'do_delete_friend':
					$Action = new FriendAction( Input::get("user_id"), Input::get("friend_id") );
					if( $Action->delete() ){
						$SUCCESS = true;
					} else {
						$TEXT = $Action->get_return_text();
					}
				break;

				case 'do_accept_request':				
					if( $Request->action( true ) ){
						$SUCCESS = true;
						$OUTPUT["friend_data"] = $Request->get_friend_bundle();
					} else {
						$TEXT = $Request->get_error_text();
					}
				break;

				case 'do_ignore_request':
					if( $Request->action( false ) ){
						$SUCCESS = true;
						$OUTPUT["friend_data"] = $Request->get_friend_bundle();
					} else {
						$TEXT = $Request->get_error_text();
					}
				break;

				case 'do_send_request':
					if( $Request->send() ){
						$SUCCESS = true;
					} else{
						$TEXT = $Request->get_error_text();
					}
				break;

				case 'do_cancel_request':
					if( $Request->cancel() ){
						$SUCCESS = true;
					} else{
						$TEXT = $Request->get_error_text();
					}
				break;

			}
		}
	}
	$OUTPUT["success"] = $SUCCESS;
	$OUTPUT["text"] = $TEXT;
	$OUTPUT["status"] = $STATUS;
	$OUTPUT["type"] = Input::get("type");
	echo json_encode($OUTPUT);

?>

<fieldset>
<legend>do_delete_friend</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_delete_friend"  placeholder="type "/>
	<input type="text" name="user_id"  placeholder="user_id.."/>
	<input type="text" name="friend_id" placeholder="friend_id.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>
