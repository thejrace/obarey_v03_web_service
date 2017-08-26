<?php

	class GameRequest implements Request {

		private $pdo, $user_id, $friend_id, $friend_bundle = array(), $game_id;

		public function __construct( $user_id, $friend_id ){
			$this->pdo = DB::getInstance();
			$this->user_id = $user_id;
			$this->friend_id = $friend_id;
		}

		public function send(){
			if( !$this->pdo->insert(DBT_GAME_REQUESTS, array(
				"user_id" => $this->user_id,
				"friend_id" => $this->friend_id,
				"date" => Common::get_datetime()
			)) ){
				$this->error_text = "Send request insert failed";
				return false;
			}
			return true;
		}

		public function is_request_sent(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_GAME_REQUESTS . " WHERE user_id = ? && friend_id = ?", array( $this->user_id, $this->friend_id ));
			return $query->count() == 1; 
		}

		public function cancel(){
			if( !$this->pdo->query("DELETE FROM " . DBT_GAME_REQUESTS . " WHERE user_id = ? && friend_id = ?", array( $this->user_id, $this->friend_id) ) ){
				$this->error_text = "Cancel request delete failed";
				return false;
			}
			return true;
		}

		public function action( $accept ){
			// ekleyen = friend_id
			// kabul eden / reddeden  = user_id
			$query = $this->pdo->query("SELECT * FROM " . DBT_GAME_REQUESTS . " WHERE user_id = ? && friend_id = ?", array( $this->friend_id, $this->user_id ));
			if( $query->count() == 1 ){
				// kabul edilmisse arkadas tablosune ekle
				// reddedilmisse sadece requests tablosundan sil
				$query_results = $query->results();
				if( $accept ){
					if( !$this->pdo->insert( DBT_USER_GAMES, array(
						"user_id"      => $this->friend_id,
						"opponents_id" => $this->user_id,
						"user_wins"	   => 0,
						"opponents_wins" => 0,
						"round"		   => 1,
						"play_turn"    => $this->user_id,
						"turn_start"   => time()
					)) ) {
						$this->error_text = "Game request insert patladı.";
						return false;
					}
				}
				$this->game_id = $this->pdo->lastInsertedId();
				if( !$this->pdo->query("DELETE FROM " . DBT_GAME_REQUESTS . " WHERE id = ?",array( $query_results[0]["id"]) ) ){
					$this->error_text = "İstek silinemedi.";
					return false;
				}

			} else {
				$this->error_text = "Böyle bir oyun isteği yok.";
				return false;
			}
			return true;
		}

		public function get_game_id(){
			return $this->game_id;
		}

		public function get_error_text(){
			return $this->error_text;
		}

		public function get_friend_bundle(){
			return $this->friend_bundle;
		}

	}