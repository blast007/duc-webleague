<?php 


function createPoll()
{
	global $site;
	global $connection;
		
	// sanitise poll variables
		
	if (isset($_POST['poll_question']))
	{
		$question = $_POST['poll_question'];
	}	
	$view_results = 0;
	if (isset($_POST['view_results']))
	{
		$view_results = intval($_POST['view_results']);
	}		
	
	if (!isset($question) || $question === '')
	{
		echo '<p>Please enter the question</p>' . "\n";
	} else 
	{
		$query = ('INSERT INTO `polls_questions` (`question`, `timeof` '
				  . ', `published`, `view_results`)'
				  . ' VALUES (' . sqlSafeStringQuotes($question) . ', now()'
				  . ', \'no\', ' . sqlSafeStringQuotes($view_results) . ')' );
		
		if (!($result = $site->execute_query('polls_questions', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('The poll could not be created due to a sql problem!');
		}
	}	
}



function deletePoll($id)
{
	global $site;
	global $connection;
	
	$query = ('DELETE FROM `polls_questions` WHERE `id` =' . sqlSafeStringQuotes($id));
		
	if (!($result = $site->execute_query('polls_questions', $query, $connection)))
	{
		$site->dieAndEndPage('The message could not be deleted due to a sql problem!');
	}	
}

function publishPoll($id, $value)
{
	global $site;
	global $connection;
	
	$query = ('UPDATE `polls_questions` SET `published` = ' . sqlSafeStringQuotes($value) . ' WHERE `id` =' . sqlSafeStringQuotes($id));
			
	if (!($result = $site->execute_query('polls_questions', $query, $connection)))
	{
	
		$site->dieAndEndPage('The message could not be published due to a sql problem!');
	}		
}


function updatePollPresentation($id, $view_results)
{
	global $site;
	global $connection;
		
	$query = ('UPDATE `polls_questions` SET `view_results` = ' . sqlSafeStringQuotes($view_results) 
		. ' WHERE id = ' . sqlSafeStringQuotes($id));
	
	if (!($result = $site->execute_query('polls_questions', $query, $connection)))
	{
		$site->dieAndEndPage('The poll could not be updated due to a sql problem!');
	}	
}	

	
function updatePollQuestion($id, $question)
{
	global $site;
	global $connection;
		
	$query = ('UPDATE `polls_questions` SET `question` = ' . sqlSafeStringQuotes($question) 
		. ' WHERE id = ' . sqlSafeStringQuotes($id));
	
	if (!($result = $site->execute_query('polls_questions', $query, $connection)))
	{
		$site->dieAndEndPage('The poll could not be updated due to a sql problem!');
	}	
}


function showPollsList($allow_manage_polls, $editedid)
{	
	global $site;
	global $connection;
	
	$query = ('SELECT pq.*, COUNT(pa.id) as answers, '
	. ' (SELECT COUNT(pv.id) FROM polls_votes pv WHERE pv.question_id = pq.id GROUP BY pq.id) as votes  FROM polls_questions pq '
	. ' LEFT JOIN polls_answers pa ON (pa.question_id = pq.id)'
	. ' GROUP BY pq.id, pq.question, pq.timeof, pq.published, pq.view_results'
	. ' ORDER BY pq.id DESC');
			
	if (!($result = @$site->execute_query('polls_questions', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of shouts results could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="./" class="simpleform">' . "\n";
	
	// input string
	echo '<div class="formrow"><label for="poll_question">Question:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="Poll question" id="poll_question" name="poll_question" value=""');
	echo '</span></div> ' . "\n";
	echo '<div class="formrow"><label for="view_results">Show results w/o voting:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="checkbox" title="Can users view poll results before voting?" id="view_results" name="view_results" value="1"');
	echo '</span></div> ' . "\n";
	echo '<div class="formrow">';
	$site->write_self_closing_tag('input type="submit" name="create" value="Create a new poll" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	
	if ($rows === (int) 0)
	{
		echo '<p class="message">No active polls.</p>' . "\n";
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	
	
	
	echo '<table id="shouts" class="big">' . "\n";
	echo '<thead><tr>' . "\n";
	echo '	<th>Date</th>' . "\n";
	echo '	<th>Question</th>' . "\n";
	echo '	<th>Number of answers </th>' . "\n";
	echo '	<th>Number of votes </th>' . "\n";
	echo '	<th>Show results <br/>before voting</th>' . "\n";
	echo '	<th>Actions</th>' . "\n";
	echo '</tr></thead>' . "\n\n";
	
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$results_list[$id]['timeof'] = $row['timeof'];
		$results_list[$id]['question'] = $row['question'];
		$results_list[$id]['view_results'] = $row['view_results'];
		$results_list[$id]['answers'] = intval($row['answers']);
		$results_list[$id]['votes'] = intval($row['votes']);
		$results_list[$id]['id'] = $row['id'];		
		$results_list[$id]['published'] = $row['published'];
	}
	unset($results_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
	
		
	// walk through the array values
	foreach($results_list as $result_entry)
	{
		echo '<tr ' . (($result_entry['published'] === 'no')? 'class="deleted"' : '') . '>' . "\n";
		echo '<td>' . $result_entry['timeof'] . '</td>' . "\n";
		if ($result_entry['id'] == $editedid) 
		{
			echo '<td><form name="edit_question" method="post" action="./">';
			$site->write_self_closing_tag('input type="hidden" name="pollid" value="' . $result_entry['id'] . '" ');
			$site->write_self_closing_tag('input type="text" title="Poll question" id="poll_question" name="poll_question" value="' . $result_entry['question'] . '"');
			$site->write_self_closing_tag('input type="submit" name="update" value="Update" id="send" class="button"');
			echo '</form></td>';
		} else
		{
			echo '<td>' . $result_entry['question'] . '</td>' . "\n";
		}
		echo '<td>' . $result_entry['answers'] . '</td>' . "\n";
		echo '<td>' . $result_entry['votes'];
		if ($allow_manage_polls) 
		{
		
			echo ' <a class="button" href="./voters.php?poll=' . htmlspecialchars($result_entry['id']) . '">Voters list</a> ';
		}
		
		echo '</td>' . "\n";
		echo '<td>';
			showResultsForm($result_entry['id'], $result_entry['view_results']);
		echo '</td>' . "\n";
	
		// show allowed actions based on permissions
		if ($allow_manage_polls)
		{
			echo '<td>';
			if ($result_entry['published'] === 'no')
			{
				if ($result_entry['answers'] > 0) 
				echo '<a class="button" href="./?publish=' . htmlspecialchars($result_entry['id']) . '" >Publish</a> ';
			}
			else echo '<a class="button" href="./?block=' . htmlspecialchars($result_entry['id']) . '" >Block</a> ';
			
			
			echo '<a class="button" href="./edit.php?poll=' . htmlspecialchars($result_entry['id']) . '">Answers</a> ';
			echo '<a class="button" href="./?edit=' . htmlspecialchars($result_entry['id']) . '">Edit</a> ';
			
			echo '<a class="button" href="./?delete=' . htmlspecialchars($result_entry['id']) . '" 
			onclick="return confirm(\'Are you sure you want to delete this poll?\')" >Delete</a> ';
			echo '</td>' . "\n";
		}
		
		echo '</tr>' . "\n\n";
			}
	unset($results_list);
	unset($result_entry);
	
	
	// no more matches to display
	echo '</table>' . "\n";
	
}

function showResultsForm($id,$show)
{
	global $site;
	
	echo '<form action="./" method="post">';
	$site->write_self_closing_tag('input type="hidden" name="change" value="' . $id .'"');
	$selected = '';
	if ($show == 1)
	{
		$selected = 'checked="checked"';
	}	
	$site->write_self_closing_tag('input type="checkbox" name="view_results" onchange="this.form.submit();" value="1" ' . $selected);
		
	echo '</form>';
}



function createAnswer($pollid)
{
	global $site;
	global $connection;
		
	// sanitise poll variables
		
	if (isset($_POST['poll_answer']))
	{
		$answer = $_POST['poll_answer'];
	}	
	
	
	if (!isset($answer) || $answer === '')
	{
		echo '<p>Please enter the answer</p>' . "\n";
	} else 
	{	
		$query = ('SELECT MAX(display_order)+1 FROM polls_answers WHERE question_id = ' . sqlSafeStringQuotes($pollid));
		$max = 0;
		if (!($result = $site->execute_query('polls_answers', $query, $connection)))
		{
			$site->dieAndEndPage('The answer could not be created due to a sql problem!');
		}
		if ($row = mysql_fetch_row($result))
		{
			$max = intval($row[0]);
		}
		
		$query = ('INSERT INTO `polls_answers` (`question_id`, `answer` '
				  . ', `display_order`)'
				  . ' VALUES (' . sqlSafeStringQuotes($pollid) . ',' . sqlSafeStringQuotes($answer) 
				  . ', ' . $max . ')' );
		
		if (!($result = $site->execute_query('polls_answers', $query, $connection)))
		{
			$site->dieAndEndPage('The answer could not be created due to a sql problem!');
		}
	}	
}



function updateAnswer($answerid)
{
	global $site;
	global $connection;
		
	// sanitise poll variables
		
	if (isset($_POST['poll_answer']))
	{
		$answer = $_POST['poll_answer'];
	}	
	
	
	if (!isset($answer) || $answer === '')
	{
		echo '<p>Please enter the answer</p>' . "\n";
	} else 
	{	
		$query = ('UPDATE `polls_answers` SET `answer`=' . sqlSafeStringQuotes($answer) 
		. ' WHERE id = ' . sqlSafeStringQuotes($answerid));
		
		if (!($result = $site->execute_query('polls_answers', $query, $connection)))
		{
			$site->dieAndEndPage('The answer could not be updated due to a sql problem!');
		}
	}	
}



function deleteAnswer($id, $pos)
{
	global $site;
	global $connection;
	
	$query = 'UPDATE polls_answers SET display_order = display_order - 1' 
	. ' WHERE display_order > ' . $pos; 
	
	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be deleted due to a sql problem!');
	}	
	
	$query = ('DELETE FROM `polls_answers` WHERE `id` =' . sqlSafeStringQuotes($id));
		
	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be deleted due to a sql problem!');
	}	
}

function resetVotes($id)
{
	global $site;
	global $connection;
	
	$query = 'DELETE FROM polls_votes WHERE answer_id = ' . $id; 
	
	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be deleted due to a sql problem!');
	}	
	
}


function MoveUpAnswer($id, $pos)
{
	global $site;
	global $connection;
	
	//first move down answer before
	$query = 'UPDATE polls_answers SET display_order = display_order + 1' 
	. ' WHERE display_order = ' . $pos; 
	

	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be moved due to a sql problem!');
	}	
	//then move up current answer
	$query = 'UPDATE polls_answers SET display_order = display_order - 1' 
	. ' WHERE id = ' . $id; 
	
	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be moved due to a sql problem!');
	}	
}

function MoveDownAnswer($id, $pos)
{
	global $site;
	global $connection;
	
	//first move up answer after
	$query = 'UPDATE polls_answers SET display_order = display_order - 1' 
	. ' WHERE display_order = ' . $pos; 
	

	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be moved due to a sql problem!');
	}	
	//then move down current answer
	$query = 'UPDATE polls_answers SET display_order = display_order + 1' 
	. ' WHERE id = ' . $id; 
	
	if (!($result = $site->execute_query('polls_answers', $query, $connection)))
	{
		$site->dieAndEndPage('The answer could not be moved due to a sql problem!');
	}	
}

	
function showAnswersList($allow_manage_polls, $pollid)
{	
	global $site;
	global $connection;
	
	$query = ('SELECT pa.*, COUNT(pv.id) as votes FROM polls_answers pa '
	. ' LEFT JOIN polls_votes pv ON (pv.answer_id = pa.id)'
	. ' WHERE pa.question_id = ' . sqlSafeStringQuotes($pollid)
	. '	GROUP BY pa.id, pa.question_id, pa.answer, pa.display_order'
	. ' ORDER BY pa.display_order');
			
	if (!($result = @$site->execute_query('polls_questions', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of answers results could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);

	
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="./edit.php?poll=' . $pollid . '" class="simpleform">' . "\n";
	
	// input string
	$site->write_self_closing_tag('input type="hidden" name="poll" value="' . $pollid . '" ');
	
	echo '<div class="formrow"><label for="poll_answer">Answer:</label> ' . "\n";
	echo '<span>';
	$site->write_self_closing_tag('input type="text" title="Poll answer" id="poll_answer" name="poll_answer" value=""');
	echo '</span></div> ' . "\n";
	echo '<div class="formrow">';
	$site->write_self_closing_tag('input type="submit" name="create" value="Add answer" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	
	if ($rows === (int) 0)
	{
		echo '<p class="message">No answers for this poll.</p>' . "\n";
		$site->dieAndEndPageNoBox();
	}
	
	
	
	
	echo '<table id="shouts" class="big">' . "\n";
	echo '<thead><tr>' . "\n";
	echo '	<th>Position</th>' . "\n";
	echo '	<th>Answer</th>' . "\n";
	echo '	<th>Number of votes </th>' . "\n";
	echo '	<th>Actions</th>' . "\n";
	echo '</tr></thead>' . "\n\n";
	
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$results_list[$id]['display_order'] = $row['display_order'];
		$results_list[$id]['answer'] = $row['answer'];
		$results_list[$id]['question_id'] = $row['question_id'];
		$results_list[$id]['votes'] = intval($row['votes']);
		$results_list[$id]['id'] = $row['id'];		
	}
	unset($results_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
		
	// walk through the array values
	foreach($results_list as $result_entry)
	{
		echo '<td>' . answerPosition($result_entry['question_id'], $result_entry['id'], $result_entry['display_order'], $rows) . '</td>' . "\n";
		echo '<td>';
			answerEdit($pollid, $result_entry['id'], $result_entry['answer'] ) ;
		echo '</td>' . "\n";
		echo '<td>' . $result_entry['votes'] . '</td>' . "\n";
	
		// show allowed actions based on permissions
		if ($allow_manage_polls)
		{
			echo '<td>';
						
			
			echo '<a class="button" href="./edit.php?poll=' . $pollid . '&amp;delete=' . htmlspecialchars($result_entry['id']) 
			. '&amp;pos=' .  htmlspecialchars($result_entry['display_order']) . '" 
			onclick="return confirm(\'Are you sure you want to delete this answer?\')" >Delete</a> ';
			echo '<a class="button" href="./edit.php?poll=' . $pollid . '&amp;reset=' . htmlspecialchars($result_entry['id']) . '"' 
			. ' onclick="return confirm(\'Are you sure you want to reset votes for this answer?\')" >Reset votes</a> ';
			echo '</td>' . "\n";
		}
		
		echo '</tr>' . "\n\n";
			}
	unset($results_list);
	unset($result_entry);
	unset($rows);
	
	
	// no more matches to display
	echo '</table>' . "\n";
}


function answerEdit($pollid, $answerid,  $answer)
{
	global $site;
	
	echo '<form method="post" action="?poll=' . $pollid . '" >';
	$site->write_self_closing_tag('input type="text" name="poll_answer" value="' . $answer . '"');
	$site->write_self_closing_tag('input type="hidden" name="answer" value="' . $answerid . '"');
	$site->write_self_closing_tag('input type="submit" name="edit" value="Change" class="button"');
	echo ' </form>';
}

function answerPosition( $pollid, $answerid,  $position, $last)
{
	$msg = '';
	if ($position > 0)
	{
		$msg .= '<a href="?poll=' . $pollid . '&amp;answer=' . $answerid . '&amp;up=' . ($position - 1) . '">^</a>'; 
	}
	$msg .= $position;
	if ($position < $last -1)
	{
		$msg .= '<a href="?poll=' . $pollid . '&amp;answer=' . $answerid . '&amp;down=' . ($position + 1) . '">v</a>'; 
	}
	return $msg;
}





	
function showVotersList($allow_manage_polls, $pollid)
{	
	global $site;
	global $connection;
	
	$query = ('SELECT pv.timeof, pv.user_id,  p.name, pv.id FROM polls_votes pv '
	. ' LEFT JOIN players p ON p.id = pv.user_id '
	. ' WHERE pv.question_id = '. sqlSafeStringQuotes($pollid) . ' ORDER BY timeof');

				
	if (!($result = @$site->execute_query('polls_questions', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of voters could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);

	
	if ($rows === (int) 0)
	{
		echo '<p class="message">No votes for this poll.</p>' . "\n";
		$site->dieAndEndPageNoBox();
	}
	
	
	
	
	echo '<table id="shouts" class="big">' . "\n";
	echo '<thead><tr>' . "\n";
	echo '	<th>Time</th>' . "\n";
	echo '	<th>Player</th>' . "\n";
	echo '</tr></thead>' . "\n\n";
	
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$results_list[$id]['timeof'] = $row['timeof'];
		$results_list[$id]['playerid'] = $row['user_id'];
		$results_list[$id]['name'] = $row['name'];
		$results_list[$id]['id'] = $row['id'];		
	}
	unset($results_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
		
	// walk through the array values
	foreach($results_list as $result_entry)
	{
		echo '<tr><td>' . $result_entry['timeof'] . '</td>' . "\n";
		echo '<td><a href="/Players/?profile=' . $result_entry['playerid'] . '">' . $result_entry['name'] . '</a></td></tr>' . "\n";
	
			}
	unset($results_list);
	unset($result_entry);
	unset($rows);
	
	
	// no more matches to display
	echo '</table>' . "\n";
}
	
	
?>