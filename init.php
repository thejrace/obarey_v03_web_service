<?php

	define("DB_NAME", "obarey_ws");
	define("DB_PASS", "WAzzabii308");
	define("DB_IP", "94.73.170.231");
	define("DBT_USERS", "users");
	define("DBT_USER_GAMES", "user_active_games");
	define("DBT_USER_FINISHED_GAMES", "user_finished_games");
	define("DBT_FRIENDS", "user_friends");
	define("DBT_FRIEND_REQUESTS", "friend_requests");
	define("DBT_GAME_REQUESTS", "game_requests");
	define("DBT_QUESTIONS", "questions");
	define("DBT_QUESTIONS_TEMP", "questions_temp");
	define("DBT_QUESTION_FEEDBACKS", "question_feedbacks");
	define("DBT_QUESTION_RATES", "question_rates");
	define("DBT_ANSWERED_QUESTIONS", "user_answered_questions");
	define("DBT_USER_QUESTION_STATS", "user_question_stats");
	define("DBT_GAME_RULES", "game_rules");
	define("DBT_UPDATE_REQUEST", "update_request");
	define("DBT_SERVER_ERROR_LOG", "server_error_log");
	define("DBT_APP_ERROR_LOG", "app_error_log");

	define("APP_VERSION", "v0.3");


	define("MAIN_DIR", "/home/ahsaphobby.net/httpdocs/obarey_webservice/");
	define("COMMON_DIR", MAIN_DIR . "common/");

	// Error output log
	ini_set('error_log', MAIN_DIR . "error.log");


	class Common {
		public static function get_datetime(){
			return date("Y-m-d") . " " . date("H:i:s");
		}

	}

	// db sifre WAzzabii308

	// Otomatik class include
	function autoload_main_classes($class_name){
		$file = COMMON_DIR . $class_name. '.php';
	    if (file_exists($file)) require_once($file);
	}
	spl_autoload_register( 'autoload_main_classes' );


	// register, game action, user_data da kullaniyorum
	$GAME_RULES = new GameRules;
	$RULES = $GAME_RULES->get();
	// class lar icin constant tanimla
	define( "POINTS_PER_QUESTION", $RULES["points_per_question"] );
	define( "YERABOS_PER_QUESTION", $RULES["yerabos_per_question"] );
	define( "ROUND_LIMIT", $RULES["round_limit"] );
	define( "GAMEPLAY_TIME", $RULES["gameplay_time"] );
	define( "PLAY_TURN_TIME", $RULES["play_turn_time"] );
	define( "FIFTY_FIFTY_JOKER_COST", $RULES["fifty_fifty_joker_cost"]);
	define( "DOUBLE_ANSWER_JOKER_COST", $RULES["double_answer_joker_cost"]);

	class ErrorLog {
		const APP_ERROR = 1,
			  SERVER_ERROR = 2;

		public static function add( $type, $text ){
			if( $type == self::APP_ERROR ){
				$table = DBT_APP_ERROR_LOG;
			} else if( $type == self::SERVER_ERROR) {
				$table = DBT_SERVER_ERROR_LOG;
			}
			DB::getInstance()->insert( $table, array(
				"log" => $text,
				"date" => Common::get_datetime()
			));
		}

	}

	class PlayTurnTimeCheck{
		private $pdo;
		public function __construct(){
			$this->pdo = DB::getInstance();
			// now - play_turn_time < turnstart --> zaman dolmus
			$query = $this->pdo->query("SELECT * FROM " . DBT_USER_GAMES . " WHERE turn_start < ?", array( ( time() - PLAY_TURN_TIME )  ));
			if( $query->count() > 0 ){
				foreach( $query->results() as $result ){
					// update et oyunu
					$Game = new Game( $result["id"], $result["play_turn"] );
					$Game->update( false, true );
				}
			}
		}
	}

	// playturn timeout olan oyunlari guncelle
	$PlayTurnTimeCheck = new PlayTurnTimeCheck();

