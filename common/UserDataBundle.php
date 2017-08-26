<?php	
	class UserDataBundle {

		private $pdo, $user_id, $data = array(), $error_output;

		public function __construct( $user_id ){
			$this->pdo = DB::getInstance();
			$this->user_id = $user_id;
		}

		public function get_question_stats(){
			$this->data["question_stats"] = array();
			$query = $this->pdo->query("SELECT * FROM " . DBT_USER_QUESTION_STATS . " WHERE user_id = ?", array($this->user_id));
			foreach( $query->results() as $result ){
				$this->data["question_stats"] = $result;
			}
		}

		public function get_game_requests(){
			$this->data["game_requests"] = array();
			$query = $this->pdo->query("SELECT * FROM " . DBT_GAME_REQUESTS . " WHERE friend_id = ?", array($this->user_id));
			foreach( $query->results() as $request ){
				$Requester = new UserDataBundle( $request["user_id"] );
				$this->data["game_requests"][] = array( "friend_id" => (int)$request["user_id"], "friend_name" => $Requester->get_user_name() ); 
			}
			return true;
		}
		public function get_game_requests_sent(){
			$this->data["game_requests_sent"] = array();
			$query = $this->pdo->query("SELECT * FROM " . DBT_GAME_REQUESTS . " WHERE user_id = ?", array($this->user_id));
			foreach( $query->results() as $request ){
				$RequestedTo = new UserDataBundle( $request["friend_id"] );
				$this->data["game_requests_sent"][] = array( "friend_id" => (int)$request["friend_id"] ); 
				//$this->data["game_requests_sent"][] = array( "friend_id" => (int)$request["friend_id"], "friend_name" => $RequestedTo->get_user_name() ); 
			}
			return true;
		}
		public function get_friend_requests(){
			$this->data["friend_requests"] = array();
			$query = $this->pdo->query("SELECT * FROM " . DBT_FRIEND_REQUESTS . " WHERE friend_id = ?", array($this->user_id));
			foreach( $query->results() as $request ){
				$Requester = new UserDataBundle( $request["user_id"] );
				$this->data["friend_requests"][] = array( "friend_id" => (int)$request["user_id"], "friend_name" => $Requester->get_user_name() ); 
			}
			return true;
		}

		public function get_friend_requests_sent(){
			$this->data["friend_requests_sent"] = array();
			$query = $this->pdo->query("SELECT * FROM " . DBT_FRIEND_REQUESTS . " WHERE user_id = ?", array($this->user_id));
			foreach( $query->results() as $request ){
				$RequestedTo = new UserDataBundle( $request["friend_id"] );
				$this->data["friend_requests_sent"][] = array( "friend_id" => (int)$request["friend_id"], "friend_name" => $RequestedTo->get_user_name() ); 
			}
			return true;
		}

		public function get_user_name(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_USERS . " WHERE id = ?", array($this->user_id));
			if( $query->count() != 1 ) {
				return "Kullanıcı bulunamadı.";
			}
			$result = $query->results();
			return $result[0]["name"];
		}
		public function get_user_data(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_USERS . " WHERE id = ?", array($this->user_id));
			if( $query->count() != 1 ) {
				$this->error_output = "Kullanıcı bulunamadı.";
				return false;
			}
			$results = $query->results();
			foreach( $results[0] as $key => $value ){
				if( $key == "id" || $key == "name" || $key == "email" ){
					$this->data[$key] = $value;
				}
			}
			return true;
		}	
		public function get_stats(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_USERS . " WHERE id = ?", array($this->user_id));
			if( $query->count() != 1 ) {
				$this->error_output = "Kullanıcı bulunamadı.";
				return false;
			}
			$results = $query->results();
			foreach( $results[0] as $key => $value ){
				if( $key == "points" || $key == "yerabos" || $key == "wins" || $key == "loses"){
						$this->data[$key] = $value;
				}
			}
			return true;
		}
		// where user_id || opponents_id kullanicinin baslatmadigi oyunlari da almak icin
		public function get_games(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_USER_GAMES . " WHERE user_id = ? || opponents_id = ?", array($this->user_id, $this->user_id));
			if( $query->count() == 0 ){
				$this->data["games"] = array();
				return true;
			}
			$unordered = array();
			foreach( $query->results() as $key => $value ){
				// karsi oyunucnun oyun baslatana gore id sini buluyoruz
				($this->user_id != $value["opponents_id"] ) ? $enemy_id = $value["opponents_id"] : $enemy_id =  $value["user_id"];
				$Enemy = new UserDataBundle( $enemy_id );
				// output array i duzeltiyoruz
				$value["opponents_name"] = $Enemy->get_user_name();
				$value["user_id"] = $this->user_id;
				$value["opponents_id"] = $enemy_id;

				// 											48saat -  ( suan - round switch time )saat   
				$value["turn_time_left"] = ceil( ( PLAY_TURN_TIME - ( time() - $value["turn_start"] ) ) / 3600 ); // saat cinsinden
				unset($value["turn_start"]);
				unset($value["opponents_name"]);
				$unordered[$key] = $value; 
			}
			// sira kullanicida olan oyunlari en basta gostermek icin siraya sok listeye
			$counter = 0;
			foreach( $unordered as $game ){
				if( $game["play_turn"] == $this->user_id ){
					$this->data["games"][] = $game;
					// sira kullanicida olanlari ilk ekle, ve sirasiz listeden kaldir
					unset( $unordered[$counter] );
				}
				$counter++;
			}
			// sira kullanicida olmayanlari sona ekle
			foreach( $unordered as $game ){
				$this->data["games"][] = $game;
			}
			return true;
		}
		public function get_finished_games(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_USER_FINISHED_GAMES . " WHERE user_id = ? || opponents_id = ?", array($this->user_id, $this->user_id));
			if( $query->count() == 0 ){
				$this->data["finished_games"] = array();
				return true;
			}
			foreach( $query->results() as $key => $value ){
				$this->data["finished_games"][] = array( "opponents_id" => $value["opponents_id"], "opponents_wins" => $value["opponents_wins"], "users_wins" => $value["users_wins"]);
			}
			return true;
		}
		public function get_friends(){
			$query = $this->pdo->query("SELECT * FROM " . DBT_FRIENDS . " WHERE friend_1_id = ? || friend_2_id = ?", array($this->user_id, $this->user_id));
			if( $query->count() == 0 ){
				$this->data["friends"] = array();
				return true;
			}
			// tum arkadaslari listele
			foreach( $query->results() as $data ){
				if( $data["friend_2_id"] != $this->user_id ){
					$Enemy = new UserDataBundle( $data["friend_2_id"] );
					$this->data["friends"][] = array( "friend_id" => $data["friend_2_id"], "friend_name" => $Enemy->get_user_name() );
				}
				if( $data["friend_1_id"] != $this->user_id ){
					$Enemy = new UserDataBundle( $data["friend_1_id"] );
					$this->data["friends"][] = array( "friend_id" => $data["friend_1_id"], "friend_name" => $Enemy->get_user_name() );
				}
			}
			return true;
		}
		public function get_data( $key = null ){
			if( isset($key) ) return $this->data[$key];
			return $this->data;
		}
		public function get_error(){
			return $this->error_output;
		}

	}