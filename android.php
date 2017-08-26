<?php

	$output = array(
		"status" => "default"
	);

	if( $_POST ){
		if( isset($_POST["type"])){

			switch($_POST["type"]){

				case 'do_register_action':
					$output = array(
						"success" => 1,
						"user_id" => 22
					);
				break;
			}
		}
	}

	// $keo = array(
	// 	"total_players" => 3,
	// 	"players" => array(
	// 		0 => array(
	// 			"name" => "Ahmet Kanbur",
	// 			"points" => 250,
	// 			"avatar" => "http://ahsaphobby.net/v2/res/img/static/avatar.png" 
	// 		),
	// 		1 => array(
	// 			"name" => "Till Lindemann",
	// 			"points" => 110,
	// 			"avatar" => "http://ahsaphobby.net/v2/res/img/static/avatar.png" 
	// 		),
	// 		2 => array(
	// 			"name" => "Mein Land",
	// 			"points" => 333,
	// 			"avatar" => "http://ahsaphobby.net/v2/res/img/static/avatar.png" 
	// 		)
	// 	)
	// );

	echo json_encode($output);