<?php
	
	require_once "init.php";

	$STATUS = "default";
	$SUCCESS = false;
	$TEXT = "";
	$OUTPUT = array();


	if( $_POST ){
		if( Input::get("type") != "" ){

			$Request = new GameRequest( Input::get("user_id"), Input::get("friend_id"));

			switch( Input::get("type") ){

				case 'do_accept_request':				
					if( $Request->action( true ) ){
						$SUCCESS = true;
						$OUTPUT["game_id"] = $Request->get_game_id();
						$OUTPUT["unix_time"] = time();
					} else {
						$TEXT = $Request->get_error_text();
					}
				break;

				case 'do_ignore_request':
					if( $Request->action( false ) ){
						$SUCCESS = true;
					} else {
						$TEXT = $Request->get_error_text();
					}
				break;

				case 'do_send_request':
					if( $Request->send() ){
						$SUCCESS = true;
					} else{
						$TEXT = $Request->get_error_text();
					}
				break;

				case 'do_cancel_request':
					if( $Request->cancel() ){
						$SUCCESS = true;
					} else{
						$TEXT = $Request->get_error_text();
					}
				break;

			}
		}
	}	

	$OUTPUT["success"] = $SUCCESS;
	$OUTPUT["text"] = $TEXT;
	$OUTPUT["status"] = $STATUS;
	$OUTPUT["type"] = Input::get("type");
	echo json_encode($OUTPUT);
	// echo json_encode($DATA);

?>

<fieldset>
<legend>do_accept_request</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_accept_request" placeholder="type "/>
	<input type="text" name="user_id"  value="10" placeholder="user_id.."/>
	<input type="text" name="friend_id"  placeholder="friend_id.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>