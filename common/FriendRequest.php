<?php

	class FriendRequest implements Request {

		private $pdo, $user_id, $friend_id, $error_text, $friend_bundle = array();
		public function __construct( $user_id, $friend_id ){
			$this->pdo = DB::getInstance();
			$this->user_id = $user_id;
			$this->friend_id = $friend_id;
		}

		public function send(){
			// test amacli kontrol
			$check_query = $this->pdo->query("SELECT * FROM " . DBT_FRIEND_REQUESTS . " WHERE user_id = ? && friend_id = ?", array($this->user_id, $this->friend_id ) );
			if( $check_query->count() == 0 ){
					if( !$this->pdo->insert(DBT_FRIEND_REQUESTS, array(
						"user_id" => $this->user_id,
						"friend_id" => $this->friend_id
					)) ){
					$this->error_text = "Send request insert failed";
					return false;
				}
			}
			return true;
		}

		public function cancel(){
			if( !$this->pdo->query("DELETE FROM " . DBT_FRIEND_REQUESTS . " WHERE user_id = ? && friend_id = ?", array( $this->user_id, $this->friend_id) ) ){
				$this->error_text = "Cancel request delete failed";
				return false;
			}
			return true;
		}

		public function action( $accept ){
			// ekleyen = friend_id
			// kabul eden / reddeden  = user_id
			$query = $this->pdo->query("SELECT * FROM " . DBT_FRIEND_REQUESTS . " WHERE user_id = ? && friend_id = ?", array( $this->friend_id, $this->user_id));
			if( $query->count() == 1 ){
				// kabul edilmisse arkadas tablosune ekle
				// reddedilmisse sadece requests tablosundan sil
				$query_results = $query->results();
				if( $accept ){
					if( !$this->pdo->insert(DBT_FRIENDS, array(
						"friend_1_id" => $this->user_id,
						"friend_2_id" => $this->friend_id
					)) ) {
						$this->error_text = "Friend insert patladı.";
						return false;
					}
				}
				if( !$this->pdo->query("DELETE FROM " . DBT_FRIEND_REQUESTS . " WHERE id = ?",array( $query_results[0]["id"]) ) ){
					$this->error_text = "İstek silinemedi.";
					return false;
				}

				$Friend = new UserDataBundle( $this->friend_id );
				$Friend->get_user_data();
				$this->friend_bundle = array(  "friend_name" => $Friend->get_data("name"), "friend_avatar" => "default"	);
			} else {
				$this->error_text = "Böyle bir arkadaşlık isteği yok.";
				return false;
			}
			return true;
		}

		public function get_error_text(){
			return $this->error_text;
		}

		public function get_friend_bundle(){
			return $this->friend_bundle;
		}
	}