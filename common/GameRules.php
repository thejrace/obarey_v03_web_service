<?php

	class GameRules {

		private $pdo, $id;

		public function __construct(){
			$this->pdo = DB::getInstance();
		}

		public function get(){
			$rules = array();
			$query = $this->pdo->query("SELECT * FROM " . DBT_GAME_RULES . " WHERE status = ?", array( 1 ));
			foreach( $query->results() as $rule ){
				$rules = array(
					"version"			   => $rule["version"],
					"points_per_question"  => $rule["points_per_question"],
					"play_turn_time" 	   => $rule["play_turn_time"],
					"gameplay_time" 	   => $rule["gameplay_time"],
					"yerabos_per_question" => $rule["yerabos_per_question"],
					"round_limit"		   => $rule["round_limit"],
					"fifty_fifty_joker_cost" => $rule["fifty_fifty_joker_cost"],
					"double_answer_joker_cost" => $rule["double_answer_joker_cost"]
				);
			}
			return $rules;
		}

		public function update_rule( $id, $rule, $new_val ){
			if( !$this->pdo->query( "UPDATE " . DBT_GAME_RULES . " SET ".$rule." = ? WHERE id = ?", array( $new_val, $this->id ) ) ){
				$this->error_text = "@hata: " . $rule . " update edilemedi.";
				return false;
			}
			return true;
		}

		public function change_status( $id ){
			if( !$this->pdo->query( "UPDATE " . DBT_GAME_RULES . " SET ".$rule." = ? WHERE id = ?", array( $new_val, $id ) ) ){
				$this->error_text = "@hata: status updated edilemedi.";
				return false;
			}
			// bir önceki aktif kurali deaktif et
			if( !$this->pdo->query("UPDATE " . DBT_GAME_RULES . " SET status = ? WHERE ( id != ? && status = ? )", array( 0, 1, $id ) ) ){
				$this->error_text = "@hata: onceki status deaktif edilemedi.";
				return false;
			}
			return true;
		}

		// her acilis sync de kullanicidan game rule versionu al, eger ondan daha yeni
		// update varsa yeni oyun kurallarini yolla
		public function check_updates( $user_version ){
			$query = $this->pdo->query("SELECT * FROM " . DBT_GAME_RULES . " WHERE status = ?", array( 1 ) )->results();
			if( $query[0]["version"] > $user_version ){
				return true;
			}
			return false;
		}

	}
