<?php

		// kullanici stats update class
	class UserAction {

		private $pdo, $user_id, $user_data = array(), $answer_flag;
		private $item_new_amount, $return_text;

		public function __construct( $user_id ){

			$this->pdo = DB::getInstance();
			$User = new UserDataBundle( $user_id );
			$User->get_stats();
			$User->get_question_stats();
			$this->user_data = $User->get_data();
			$this->user_id = $user_id;

		}

		//@no_answer => pas gecilmis veya timeout
		public function update_stats( $answer, $pas_or_timeout = false ){
			if( $answer ){
				$this->answer_flag = true;
			}

			$this->update_points();
			$this->update_yerabos();
			$this->update_question_stats( $pas_or_timeout );

		}

		private function update_points(){
			if( $this->answer_flag ){
				$this->pdo->query("UPDATE " . DBT_USERS . " SET points = ? WHERE id = ?",
					array( ($this->user_data["points"] + POINTS_PER_QUESTION ), $this->user_id));
			}
			
		}

		private function update_yerabos(){
			if( $this->answer_flag ){
				$this->pdo->query("UPDATE " . DBT_USERS . " SET yerabos = ? WHERE id = ?",
					array( ($this->user_data["yerabos"] + YERABOS_PER_QUESTION ), $this->user_id));
			}	
		}

		public function use_yerabos( $amount ){
			$Bundle = new UserDataBundle( $this->user_id );
			$Bundle->get_stats();
			$current_amount = $Bundle->get_data("yerabos");
			//$current_amount = $BundleData["yerabos"];
			if( $current_amount - $amount >= 0 ){
				if(!$this->pdo->query("UPDATE " . DBT_USERS . " SET yerabos = ? WHERE id = ?", array( ( $current_amount - $amount ), $this->user_id ))){
					$this->return_text = "Useyerabos update error.";
					return false;
				}
			} else {
				$this->return_text = "Useyerabos error.Yetersiz yerabos.";
				return false;
			}
			$this->item_new_amount = ( $current_amount - $amount );
			return true;
		}

		// soru istatistiklerini guncelleme
		private function update_question_stats( $pas_or_timeout ){
			if( $pas_or_timeout ){
				// pas veya timeout olduysa sadece cevaplanan soru sayisini arttiriyoruz
				$this->pdo->query("UPDATE " . DBT_USER_QUESTION_STATS . " SET answered = ? WHERE user_id = ?",
					array( ($this->user_data["question_stats"]["answered"] + 1), $this->user_id) );	
			} else {
				if( $this->answer_flag ){
					$this->pdo->query("UPDATE " . DBT_USER_QUESTION_STATS . " SET answered = ?, correct_count = ? WHERE user_id = ?",
						array( ($this->user_data["question_stats"]["answered"] + 1), ($this->user_data["question_stats"]["correct_count"] + 1), $this->user_id) );
				} else {
					$this->pdo->query("UPDATE " . DBT_USER_QUESTION_STATS . " SET answered = ?, wrong_count = ? WHERE user_id = ?",
						array( ($this->user_data["question_stats"]["answered"] + 1), ($this->user_data["question_stats"]["wrong_count"] + 1), $this->user_id) );
				}
			}
		}

		public function get_new_yerabos_amount(){
			return $this->item_new_amount;
		}


		public function get_return_text(){
			return $this->return_text;
		}
	}