<?php

	
	// appten gelen her play attemptte soru gonderilmesi,
	// gameplay timeout kontrolu ve sorunun silinmesini handle eden class
	class QuestionRequest {

		private $pdo, $question_data = array(), $user_id, $game_id;
		public function __construct( $user_id, $game_id ){
			$this->pdo = DB::getInstance();
			$this->user_id = $user_id;
			$this->game_id = $game_id;
		}


		public function timeout_check(){
			$temp_check = $this->pdo->query("SELECT * FROM " . DBT_QUESTIONS_TEMP . " WHERE game_id = ? ", array( $this->game_id ) );
			if( $temp_check->count() == 1 ){
				$temp_data = $temp_check->results();
				if( ( time() - $temp_data[0]["timestamp"] ) > 30 ) return true;
			}
			return false;
		}

		public function delete(){
			$this->pdo->query("DELETE FROM " . DBT_QUESTIONS_TEMP . " WHERE game_id = ? ", array( $this->game_id ) );
		}

		public function make(){

			$unanswered_questions = array();
			$answered_questions = array();
			$all_questions = $this->pdo->query("SELECT * FROM " . DBT_QUESTIONS)->results();

			// cevaplanmis sorulari listele
			foreach( $this->pdo->query("SELECT * FROM ". DBT_ANSWERED_QUESTIONS . " WHERE user_id = ?", array( $this->user_id) )->results() as $answered_question ){
				$answered_questions[] = $answered_question["question_id"];
			}
			// tum sorulardan cevaplanmis olanlari ayiklayip cevaplanmamislara ekle
			foreach( $all_questions as $question ){
				if( !in_array( $question["id"], $answered_questions ) ){
					$unanswered_questions[] = $question;
					// cevaplanmamis ilk soruyu al donguyu durdur, gerek yok
					break;
				}
			}
			if( count( $unanswered_questions ) == 0 ){
				// en bastan cevaplanmamislari ayiklamadan al
				//return false;
			}	

			$this->question_data = array(
				"question" => $unanswered_questions[0]["question"],
				"options" => array(
					$unanswered_questions[0]["option_0"], $unanswered_questions[0]["option_1"], $unanswered_questions[0]["option_2"], $unanswered_questions[0]["option_3"] ),
				"answer" => $unanswered_questions[0]["correct_answer"]
				);

			$this->pdo->insert( DBT_QUESTIONS_TEMP, array(
				"game_id" => $this->game_id,
				"question_id" => $unanswered_questions[0]["id"],
				"timestamp" => time()
			));

			// burada gonderilen sorunun id yi cevaplanmis tablosuna ekle
			// BURADA DEGIL GAME ACTION DA CEVAPLARSA
		}

		public function get_question(){
			return $this->question_data;
		}

	}