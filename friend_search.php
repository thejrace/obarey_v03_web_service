<?php
	
	require_once "init.php";

	$STATUS = "default";
	$SUCCESS = false;
	$TEXT = "";
	$OUTPUT = array();

	class QueryExplode{

		private $query, $query_vals = array();
		public function explode_string( $string, $sql_key ){
			$this->query = "";
			// Gelen kelimeleri ayır
			$this->query_vals  = explode( " ", trim( Input::get("query") ) );

			$count = count( $this->query_vals );
			foreach( $this->query_vals as $str => $val ){
				// Boş olmayanları 3 arama syntax ı olarak düzenle
				if( $val != "" ) {
					$this->query_vals[] = '%'.$val.'%';
					$this->query_vals[] = $val.'%';
					$this->query_vals[] = '%'.$val;
				}
				// SQL syntax eklenmemiş ve boşları sil
				unset( $this->query_vals[$str] );	
			}

			// LIKE syntaxını ekle
			$counter = 0;
			foreach( $this->query_vals as $r ){
				$counter++;

				if( $counter == 1  ) {
					$this->query .= " ".$sql_key." LIKE ?  ";
				} else {
					$this->query .= " || ".$sql_key." LIKE ?  ";
				}
			}
		}
		public function get_sql(){
			return $this->query;
		}
		public function get_sql_vals(){
			return $this->query_vals;
		}

	}

	class FriendSearch extends QueryExplode {

		private $pdo, $results = array(), $return_text = "";
		public function __construct( $user_id, $query ){
			$this->pdo = DB::getInstance();
			$this->explode_string( "@".$query, "name" );

			$User = new UserDataBundle( $user_id );
			$User->get_friend_requests_sent();
			$User->get_friends();

			// uyan tum kullanicilari al
			$search = $this->pdo->query("SELECT * FROM " . DBT_USERS . " WHERE " . $this->get_sql(), $this->get_sql_vals() );
			if( $search->count() > 0 ){
				foreach( $search->results() as $res ){
					// kendisini ekleme
					if( $res["id"] != $user_id ){
						$this->results[] = array(
							"friend_id" 	=> $res["id"],
							"friend_name" 	=> $res["name"],
							"friend_avatar" => "default",
							"request_sent" 	=> false
						);
					}
				}
			}

			// zaten arkadas olanlari ayikla
			$friend_ids = array();
			foreach( $User->get_data("friends") as $friend ) $friend_ids[] = $friend["friend_id"];
			$counter = 0;
			foreach( $this->results as $user ){	
				if( in_array( $user["friend_id"], $friend_ids ) ) unset( $this->results[$counter] );
				$counter++;
			}
			// duzelt keyleri 
			rsort( $this->results );
				
			// zaten gonderilmislerin sent flag i true yap
			// istek gonderilen kullanici id lerini listele
			$sent_requests_friend_ids = array();
			$counter = 0;
			foreach( $User->get_data("friend_requests_sent") as $sent_request ){
				$sent_requests_friend_ids[] = $sent_request["friend_id"];
			}
			foreach( $this->results as $user ){
				if( in_array( $user["friend_id"], $sent_requests_friend_ids ) ) $this->results[$counter]["request_sent"] = true;
				$counter++;
			}
			// yukarida unset yaptigimiz icin array keyleri tekrar default hale getiriyoruz
			// yapmayinca jsonarray olmuyor results
			rsort( $this->results );
		}

		public function get_results(){
			return $this->results;
		}
		public function get_return_text(){
			return $this->return_text;
		}
	}


	if( $_POST ){
		if( Input::get("query") != "" ){
			$FriendSearch = new FriendSearch( Input::get("user_id"), Input::get("query") );
			$OUTPUT["results"] = $FriendSearch->get_results();
			echo $FriendSearch->get_return_text();
		}
	}

	$OUTPUT["success"] 	= $SUCCESS;
	$OUTPUT["text"] 	= $TEXT;
	$OUTPUT["status"] 	= $STATUS;
	$OUTPUT["type"] 	= Input::get("type");
	echo json_encode($OUTPUT);
	// print_r( $OUTPUT );
?>

<fieldset>
<legend>do_search</legend>
<form action="" method="post">
	<input type="text" name="query" placeholder="query..." />
	<input type="text" name="user_id"  value="10" placeholder="user_id.."/>
	<input type="submit" value="ara" />
</form>
</fieldset>