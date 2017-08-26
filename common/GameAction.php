<?php

	// kullanicinin oyundaki hareketlerini handle eden class
	class GameAction {

		/*
        * action_type
        *   1 - cevap
        *   2 - pas
        *   3 - sure bitti
        * */
		private $pdo, $user_id, $action_type, $game_id, $game_data = array(), $question_id, $user_answer_pos, $app_answer_check, $round, $game_finished_flag = false, $cheat_flag = false,
				$fifty_fifty_joker, $double_answer_joker, $question_rate = false, $question_feedback = false, $return_text;

		// @app_answer_check : appte yaptigim dogruluk kontrolu ( burayla farkli cikarsa hack olabilir ??? :D )
		// @user_answer_pos : kullanicinin sectigi şık
		public function __construct( $input_array ){
			$this->pdo = DB::getInstance();
			$this->user_id = $input_array["user_id"];
			$this->game_id = $input_array["game_id"];
			$this->action_type = $input_array["action_type"];
			$this->app_answer_check = $input_array["is_correct"];
			$this->user_answer_pos = $input_array["answer_pos"];
			$this->round = $input_array["round"]; // oynanan round 
			$this->fifty_fifty_joker = ( $input_array["fifty_fifty_joker"] === "true");
			$this->double_answer_joker = ( $input_array["double_answer_joker"] === "true");
			if( isset( $input_array["question_feedback"] ) ) $this->question_feedback = $input_array["question_feedback"];
			if( isset( $input_array["question_rate"] ) ) $this->question_rate = $input_array["question_rate"];
		}

		public function get_question(){
			$question_query = $this->pdo->query( "SELECT * FROM " . DBT_QUESTIONS_TEMP . " WHERE game_id = ?", array( $this->game_id ))->results();
			$this->question_id = $question_query[0]["question_id"];

			// oylama ve hata bildirimlerini al
			$this->handle_question_feedback();

			// temp soruyu sil
			$this->pdo->query("DELETE FROM " . DBT_QUESTIONS_TEMP . " WHERE id = ? ", array( $question_query[0]["id"] ) );
		}

		private function handle_question_feedback(){
			
			
			if( $this->question_feedback ){
				$this->pdo->insert(DBT_QUESTION_FEEDBACKS, array(
					"question_id" => $this->question_id,
					"user_id" 	  => $this->user_id,
					"feedback"    => $this->question_feedback,
					"date"		  => Common::get_datetime()
				));
			}

			if( $this->question_rate ){
				$check_query = $this->pdo->query( "SELECT * FROM " . DBT_QUESTION_RATES . " WHERE question_id = ?", array( $this->question_id ) )->results();
				// onceden oy verilmisse soruya direk guncelleme yap
				if( isset( $check_query[0] ) ){
					if( $this->question_rate == 1 ){
						$this->pdo->query("UPDATE " . DBT_QUESTION_RATES . " SET likes = ? WHERE question_id = ?", array( ($check_query[0]["likes"] + 1), $this->question_id));
					} else {
						$this->pdo->query("UPDATE " . DBT_QUESTION_RATES . " SET dislikes = ? WHERE question_id = ?", array(($check_query[0]["dislikes"] + 1), $this->question_id));
					}
				// onceden oy verilmemisse yeni kayit ekle tabloya	
				} else {
					if( $this->question_rate == 1 ){
						$this->pdo->insert(DBT_QUESTION_RATES, array(
							"question_id" => $this->question_id,
							"likes"		  => 1,
							"dislikes"	  => 0
						));
					} else {
						$this->pdo->insert(DBT_QUESTION_RATES, array(
							"question_id" => $this->question_id,
							"likes"		  => 0,
							"dislikes"	  => 1
						));
					}
				}
			}
		}

		public function check_answer(){
			$answer_query = $this->pdo->query("SELECT * FROM " . DBT_QUESTIONS . " WHERE id = ?",array($this->question_id))->results();
			if( $answer_query[0]["correct_answer"] == $this->user_answer_pos ){
				// app teki kontrolle uyuyorsa
				if( $this->app_answer_check ){
					return true;
				}
			} else {
				return false;
			}
		}

		public function handle_jokers(){
			if( $this->get_joker_used_flag() ){
				$yerabos_cost = 0;

				if( $this->fifty_fifty_joker ) $yerabos_cost += FIFTY_FIFTY_JOKER_COST;
				if( $this->double_answer_joker ) $yerabos_cost += DOUBLE_ANSWER_JOKER_COST;



				if( $yerabos_cost > 0 ){
					$Action = new UserAction( $this->user_id );
					if( !$Action->use_yerabos( $yerabos_cost ) ){
						$this->return_text = $Action->get_return_text();
						return false;
					}
					$this->return_text = $Action->get_new_yerabos_amount();
				}
			}
			return true;
		}

		public function make(){
			$this->get_question();
			if( !$this->handle_jokers() ){
				return false;
			}
			$Game = new Game( $this->game_id, $this->user_id );
			switch( $this->action_type ){
				// cevap
				case 1:
					$Game->update( $this->check_answer() );
				break;

				// pas
				case 2:
					$Game->update( false, true );
				break;

				// timeout
				case 3:
					$Game->update( false, true );
				break;	

			}

			// 30. tursa son oyundu bu demek bitir oyunu
			if( $this->round == ROUND_LIMIT ){
				// add finished game
				//$Game->finish_game();
				$this->game_finished_flag = true;
			}
			$this->game_data = $Game->get_data();
		}

		public function get_game_finished_flag(){
			return $this->game_finished_flag;
		}

		public function get_game_data(){
			return $this->game_data;
		}

		public function get_joker_used_flag(){
			return $this->fifty_fifty_joker || $this->double_answer_joker;
		}

		public function get_return_text(){
			return $this->return_text;
		}

	}