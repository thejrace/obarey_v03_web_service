<?php
	
	require_once "init.php";

	$STATUS = "default";
	$SUCCESS = false;
	$TEXT = "";
	$OUTPUT = array();
	$GAME_RULES_UPDATE_NOTF = false;

	if( $_POST ){
		if( Input::get("type") != "" ){

			switch( Input::get("type") ){

				case 'do_get_user_data':
					$Bundle = new UserDataBundle( Input::get("user_id") );
					if( $Bundle->get_stats() && $Bundle->get_friends() && $Bundle->get_games() && $Bundle->get_friend_requests() && $Bundle->get_game_requests() && $Bundle->get_game_requests_sent() ){
						$OUTPUT = $Bundle->get_data(); 
						$SUCCESS = true;
					} else {
						$TEXT = $Bundle->get_error();
					}
				break;

				case 'do_get_user_active_games':
					$Bundle = new UserDataBundle( Input::get("user_id") );
					if( $Bundle->get_games() ){
						$OUTPUT = $Bundle->get_data(); 
						$SUCCESS = true;
					} else {
						$TEXT = $Bundle->get_error();
					}
				break;

				case 'do_get_friend_requests':
					$Bundle = new UserDataBundle( Input::get("user_id") );
					if( $Bundle->get_friend_requests() ){ 
						$OUTPUT = $Bundle->get_data();
						$SUCCESS = true;
					} else {
						$TEXT = $Bundle->get_error_text();
					}
				break;

				case 'do_get_game_requests':
					$Bundle = new UserDataBundle( Input::get("user_id") );
					if( $Bundle->get_game_requests() ){
						$OUTPUT = $Bundle->get_data(); 
						$SUCCESS = true;
					} else {
						$TEXT = $Bundle->get_error();
					}
				break;

				case 'do_use_yerabos':
					$Action = new UserAction( Input::get("user_id") );
					if( $Action->use_yerabos( Input::get("yerabos_amount") ) ) {
						$OUTPUT["yerabos"] = $Action->get_new_yerabos_amount();
						$SUCCESS = true;
					}
				break;

			}

			// oyun kurallari guncellenmisse yeni kurallari gonder
			if( Input::get("game_rules_version") != "" ){
				if( $GAME_RULES->check_updates( Input::get("game_rules_version") ) ){
					$GAME_RULES_UPDATE_NOTF = true;
					$OUTPUT["game_rules"] = $RULES;
				}
			}

		}
	}
	$OUTPUT["game_rules_update_flag"] = $GAME_RULES_UPDATE_NOTF;
	$OUTPUT["success"] = $SUCCESS;
	$OUTPUT["text"] = $TEXT;
	$OUTPUT["status"] = $STATUS;
	$OUTPUT["type"] = Input::get("type");
	echo json_encode($OUTPUT);
	// echo '<pre>';
	// print_r($OUTPUT);
?>


<fieldset>
<legend>do_get_user_data</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_get_user_data" placeholder="type "/>
	<input type="text" name="user_id"  value="10" placeholder="user_id.."/>
	<input type="text" name="game_rules_version" placeholder="gamerulesversion.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>

<fieldset>
<legend>do_get_user_active_games</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_get_user_active_games" />
	<input type="text" name="user_id"  value="10" placeholder="user_id.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>

<fieldset>
<legend>do_get_friend_requests</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_get_friend_requests"  placeholder="type "/>
	<input type="text" name="user_id"  value="10" placeholder="user_id.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>

<fieldset>
<legend>do_get_game_requests</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_get_game_requests" />
	<input type="text" name="user_id"  value="10" placeholder="user_id.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>



