<?php
	
	require_once "init.php";

	$STATUS = "default";
	$SUCCESS = false;
	$TEXT = "";
	$OUTPUT = array();

	if( $_POST ){
		if( Input::get("type") != "" ){

			switch( Input::get("type") ){

				case "do_get_question":
					$QuestionRequest = new QuestionRequest( Input::get("user_id"), Input::get("game_id") );
					if( $QuestionRequest->timeout_check() ){
						$OUTPUT["timeout_flag"] = true;

						// yanlis cevap vermis gibi kabul et
						$Game = new Game( Input::get("game_id"), Input::get("user_id") );
						$Game->update( false );

						$QuestionRequest = new QuestionRequest( Input::get("user_id"), Input::get("game_id"));
						$QuestionRequest->delete();

					} else {
						$QuestionRequest->make();
						$OUTPUT["question_data"] = $QuestionRequest->get_question();
						$OUTPUT["timeout_flag"] = false;
					}
					
				break;

				case "do_update_game":

						// if( !feedback.equals("") ) params.put("question_feedback", feedback );
        				//if( questionRate == 1 || questionRate == 2 ) params.put("question_rate", String.format("%s", questionRate ) );

					$Action = new GameAction ( Input::escape($_POST) );
					$Action->make();
					//if( $Action->get_joker_used_flag() ){
						$User = new UserDataBundle( Input::get("user_id") );
						$User->get_stats();
						$OUTPUT["yerabos"] = $User->get_data("yerabos");
					//}
					$OUTPUT["game_data"] = $Action->get_game_data();
					$OUTPUT["answer_valid"] = Input::get("is_correct");
					$OUTPUT["game_finished_flag"] = $Action->get_game_finished_flag();
					$TEXT = $Action->get_return_text();
					
					// return game object ve answer_valid
				break;

			}
		}

	}
	$OUTPUT["success"] = $SUCCESS;
	$OUTPUT["text"] = $TEXT;
	$OUTPUT["status"] = $STATUS;
	$OUTPUT["type"] = Input::get("type");
	// $OUTPUT["0INPUT"] = $_POST;

	echo json_encode($OUTPUT);
	// echo '<pre>';
	// print_r($OUTPUT);

?>



<fieldset>
<legend>do_update_game</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_update_game" placeholder="type "/>
	<input type="text" name="user_id"  value="99" placeholder="user_id.."  />
	<input type="text" name="game_id"  value="37" placeholder="game_id.."  />

	<input type="text" name="question_feedback" value="feedback text" />
	<input type="text" name="question_rate" placeholder="question_rate" />

	<input type="text" name="round" placeholder="round.."  />
	<input type="text" name="action_type" placeholder="action_type.."  />
	<input type="text" name="is_correct" placeholder="is_correct_flag"  />
	<input type="text" name="answer_pos" value="1" placeholder="answer_pos.."  />


	<input type="submit" value="gonder" />
</form>
</fieldset>

<fieldset>
<legend>do_get_question</legend>
<form action="" method="post">
	<input type="text" name="type" value="do_get_question" placeholder="type "/>
	<input type="text" name="game_id"  value="37" placeholder="game_id.."/>
	<input type="submit" value="gonder" />
</form>
</fieldset>


<?php echo time(); ?>



