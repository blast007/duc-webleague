<?php

	$viewerid = (int) getUserID();

	if ($viewerid === 0 || !isset($_SESSION['allow_vote_polls']) || !($_SESSION['allow_vote_polls'] === true))
	{
		// not logged		
	} else 
	{
		if (isset($_POST['vote']) && isset($_POST['poll']) && isset($_POST['answer']))
		{
			$poll = intval($_POST['poll']);
			$answer = intval($_POST['answer']);
			voteInPoll($poll, $answer, $viewerid);
		}		
		
		showPoll($viewerid);	
	}
	
function voteInPoll($poll, $answer, $viewerid)
{
	global $site;
	global $connection;
	if (doesUserVote($poll, $viewerid)) return;
	
	$query = 'INSERT INTO polls_votes (question_id, answer_id, timeof, user_id) '
	. ' VALUES (' . sqlSafeStringQuotes($poll) . ',' . sqlSafeStringQuotes($answer) . ', ' . sqlSafeStringQuotes(date('Y-m-d H:i:s'))
	. ',' . sqlSafeStringQuotes($viewerid) . ')'; 
	$result = ($site->execute_query('polls_votes', $query, $connection));	
}

	
function showPoll($viewerid) 
{
	global $site;
	global $connection;
	
	$pollid = 0;
	$show_results = 0;
	//first check if any active polls
	$query = 'SELECT * FROM `polls_questions` '
	. ' WHERE published = \'yes\' ';
	$result = ($site->execute_query('polls_questions', $query, $connection));
	if (!$result || mysql_num_rows($result) === 0)	return;		
	else 
	{
		$row = mysql_fetch_array($result);
		$pollid = $row['id'];
		$show_results = intval($row['view_results']);
		$question = $row['question'];		
	}
	
	$voted = doesUserVote($pollid, $viewerid);
	
	//get answers
	$query = ('SELECT pa.*, COUNT(pv.id) as votes FROM polls_answers pa '
	. ' LEFT JOIN polls_votes pv ON (pv.answer_id = pa.id)'
	. ' WHERE pa.question_id = ' . sqlSafeStringQuotes($pollid)
	. '	GROUP BY pa.id, pa.question_id, pa.answer, pa.display_order'
	. ' ORDER BY pa.display_order');
			
	if (!($result = @$site->execute_query('polls_questions', $query, $connection)))
	{
		return;
	}
	$rows = (int) mysql_num_rows($result);
	
	// display message overview
	$results_list = Array (Array ());
	$maxanswers = 1;
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$results_list[$id]['display_order'] = $row['display_order'];
		$results_list[$id]['answer'] = $row['answer'];
		$results_list[$id]['question_id'] = $row['question_id'];
		$results_list[$id]['votes'] = intval($row['votes']);
		$results_list[$id]['id'] = $row['id'];		
		if ($results_list[$id]['votes'] > $maxanswers) $maxanswers = $results_list[$id]['votes'];
		
	}
	unset($results_list[0]);
	// query result no longer needed
	mysql_free_result($result);
	
	
	echo '<div id="poll">' . "\n";
	echo '<h2 class="polls"><span>Polls</span></h2>';
	echo '<div class="main-box">';
	echo '<h3>' . htmlent($question) . '</h3>';
	
	if (!$voted)
	{
		echo '<form name="pollvote" action="./" method="post">' . "\n";
		$site->write_self_closing_tag('input type="hidden" name="poll" value="' . $results_list[$id]['question_id'] . '"');
		echo '<ul class="poll-answers">';
		// walk through the array values
		foreach($results_list as $result_entry)
		{
			echo '<li>' . "\n";
			if ($show_results)
			{
				echo '<span class="votes">' . $result_entry['votes'] . '</span>';
			}
			$site->write_self_closing_tag('input type="radio" name="answer" value="' . $result_entry['id'] . '"');
			echo htmlent($result_entry['answer']);
			if ($show_results)
			{
				echo '<div class="pbar"> ';
				$length = floor(($result_entry['votes']) / ($maxanswers)*100);
				echo '<span style="width:' . $length . '%"></span>'; 
			}
			
			
			echo '</li>';			
		}
		echo '</ul>';
		$site->write_self_closing_tag('input type="submit" name="vote" value="Vote" class="button"');
		echo '</form>';
		
		unset($results_list);
		unset($result_entry);						
	}	else
	{
		echo '<ul class="poll-answers">';
		// walk through the array values
		foreach($results_list as $result_entry)
		{
			echo '<li>' . "\n";
			echo '<span class="votes">' . $result_entry['votes'] . '</span>' . htmlent($result_entry['answer']);
			echo '<div class="pbar"> ';
			$length = floor(($result_entry['votes']) / ($maxanswers)*100);
			echo '<span style="width:' . $length . '%"></span>'; 
			echo '</div></li>';			
		}
		echo '</ul>';
		
	}
	
	
	echo '</div></div>';
	
	
}	
	
	
	
function doesUserVote($pollid, $viewerid)
{
	global $site;
	global $connection;
	
	//check if user already vote 
	$voted = false;
	$query = 'SELECT * FROM `polls_questions` pq INNER JOIN polls_votes pv ON pv.question_id = pq.id '
	. ' WHERE user_id = ' . $viewerid . ' AND pq.id = ' . $pollid;
	$result = ($site->execute_query('polls_questions', $query, $connection));
	if ($result && mysql_num_rows($result) > 0)
	{
		$voted = true;
	}
	return $voted;
}
?>	