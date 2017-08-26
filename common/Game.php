<?php

	// aktif bir oyunun guncellenmesi, bitirilmesini handle eden class
	// oyunu guncelledikten sonra kullanici stats update
	class Game {

		private $pdo, $id, $data = array(), $user_id;

		public function __construct( $id, $user_id ){
			$this->pdo = DB::getInstance();
			$this->id = $id;
			$this->user_id = $user_id;
		}


		// @no_answer => timeout veya pas dediginde kullanici soru istatistiklerinde
		// yanlis - bos ayrimi yapmak icin
		public function update( $answer_flag, $pas_or_timeout = false ){

			$UserAction = new UserAction( $this->user_id );

			// kullanici istatistiklerini guncelle
			$UserAction->update_stats( $answer_flag, $pas_or_timeout );

			$query_results = $this->pdo->query("SELECT * FROM " . DBT_USER_GAMES . " WHERE id = ?", array( $this->id ) )->results();
			if( $query_results[0]["user_id"] == $this->user_id ){
				// user_id game tablosundaki user_id ise
				// TOPLAAA SQL stringi bide val array i yap topla kodu
				if( $answer_flag ){
					$this->pdo->query("UPDATE " . DBT_USER_GAMES . " SET user_wins = ?, round = ?, turn_start = ? WHERE id = ?",
						array( ( $query_results[0]["user_wins"] + 1), $query_results[0]["round"] + 1, time(), $this->id ) );
				} else {
					$this->pdo->query("UPDATE " . DBT_USER_GAMES . " SET play_turn = ?, round = ?, turn_start = ? WHERE id = ?",
						array( ( $query_results[0]["opponents_id"] ), $query_results[0]["round"] + 1, time(), $this->id ) );
				}
			} else {
				// user_id game tablosundaki opponents_id ise

				if( $answer_flag ){
					$this->pdo->query("UPDATE " . DBT_USER_GAMES . " SET opponents_wins= ?, round = ?, turn_start = ? WHERE id = ?",
						array( ( $query_results[0]["opponents_wins"] + 1 ), $query_results[0]["round"] + 1, time(), $this->id ) );
				} else {
					$this->pdo->query("UPDATE " . DBT_USER_GAMES . " SET play_turn = ?, round = ?, turn_start = ? WHERE id = ?",
						array( ( $query_results[0]["user_id"] ), ( $query_results[0]["round"] + 1 ), time(), $this->id ) );
				}

			}

			// guncellenmis oyunu al
			$updated_game = $this->pdo->query("SELECT * FROM " . DBT_USER_GAMES . " WHERE id = ?", array( $this->id ) )->results();
			$this->data = $updated_game[0];
			$this->data["turn_time_left"] = ceil( ( PLAY_TURN_TIME - ( time() - $updated_game[0]["turn_start"] ) ) / 3600 );

		}

		public function get_data(){
			return $this->data;
		}

		public function finish_game(){
			// active gamesten sil, finished games e ekle
		}	

	}