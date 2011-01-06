<?php
	if (!isset($site))
	{
		die('This file is meant to be only included by other files!');
	}
	
	// this file deals with entering matches only
	if (!isset($_GET['enter']) && !isset($_GET['edit']) && !isset($_GET['delete']))
	{
		$site->dieAndEndPage();
	}
	
	// misc functions
	require('match.php');
	
	$confirmed = 0;
	if (isset($_POST['confirmed']))
	{
		$confirmed = intval($_POST['confirmed']);
	}
	
	permissionsCheck();
	sanityCheck($confirmed);
	
	// start processing cases
	
	if ($confirmed === 0)
	{
		// display entering form
		open_form();
		
		// this step is already the confirmation step for deleting a match
		if (isset($_GET['delete']))
		{
			generate_confirmation_key();
		}			
		
		$site->write_self_closing_tag('input type="hidden" name="confirmed" value="1"');
		
		
		// user got sent back because of wrong data in the form for instance
		if (isset($_POST['team_id1']) && isset($_POST['team_id2']) && isset($_POST['team1_points']) && isset($_POST['team2_points']) && isset($_POST['duration']))
		{
			show_form($_POST['team_id1'], $_POST['team_id2'], $_POST['team1_points'], $_POST['team2_points'], $_POST['duration'], $readonly=false);
		} elseif (isset($_GET['edit']) || isset($_GET['delete']))
		{
			$query = ('SELECT `timestamp`, `team1_teamid`, `team2_teamid`, `team1_points`, `team2_points` , `duration`'
					  . ' FROM `matches` WHERE `id`=' . sqlSafeStringQuotes($match_id)
					  . ' LIMIT 1');
			if (!($result = @$site->execute_query('matches', $query, $connection)))
			{
				$site->dieAndEndPage('Could not look up the data of match #' . sqlSafeString($match_id) . '.');
			}
			
			// check if match exists
			if (mysql_num_rows($result) < 1)
			{
				echo '<p>This match does not exist in database.</p><p><a class="button" href="./">Back to match overview</a></p>' . "\n";
				$site->dieAndEndPage();
			}
			
			// compute both time and day from timestamp data
			while($row = mysql_fetch_array($result))
			{
				$timestamp = $row['timestamp'];
				$offset = strpos($timestamp, ' ');
				$match_day = substr($timestamp, 0, $offset);
				$match_time = substr($timestamp, ($offset+1));
				
				$duration = $row['duration'];
				
				$team_id1 = intval($row['team1_teamid']);
				$team_id2 = intval($row['team2_teamid']);
				
				$team1_caps = intval($row['team1_points']);
				$team2_caps = intval($row['team2_points']);
			}
			mysql_free_result($result);
			
			show_form($team_id1, $team_id2, $team1_caps, $team2_caps, $duration, $readonly=isset($_GET['delete']));
		} else
		{
			// fill unknown values with zeros
			// team id 0 is reseved and does not exist in db
			show_form(0, 0, 0, 0, 15, $readonly=false);
		}
		
		echo '<div>';
		
		if (isset($_GET['enter']))
		{
			$site->write_self_closing_tag('input type="submit" name="match_enter_unconfirmed" value="Enter the new match" id="send" class="button l9"');
		} elseif (isset($_GET['edit']))
		{
			$site->write_self_closing_tag('input type="submit" name="match_edit_confirmed" value="Edit the match" id="send" class="button l9"');
		} else
		{
			$site->write_self_closing_tag('input type="submit" name="match_delete_confirmed" value="Delete the match" id="send" class="button l9"');
		}
		echo '</div>' . "\n";
		echo '</form>' . "\n";
		
		// done with the first step form
		$site->dieAndEndPage();
	}
	
	
	
	// user has specified team, date and time and scores
	if ($confirmed === 1)
	{
		// display confirmation form
		open_form();
		
		if ($similarMatchFound)
		{
			// include confirmation about similar match
			$site->write_self_closing_tag('input type="hidden" name="similar_match" value="1"');
		}
		
		// pass team id's as $_POST (passing it with readonly <select> doesn't work somehow)
		$site->write_self_closing_tag('input type="hidden" name="team_id1" value="'
									  . htmlspecialchars($team_id1) . '"');
		$site->write_self_closing_tag('input type="hidden" name="team_id2" value="'
									  . htmlspecialchars($team_id2) . '"');
		
		$site->write_self_closing_tag('input type="hidden" name="confirmed" value="2"');
		
		// fill in the data submitted
		show_form($team_id1, $team_id2, $team1_caps, $team2_caps, $duration, $readonly=true);
		
		
		generate_confirmation_key();
		
		echo '<div>';
		if (isset($_GET['enter']))
		{
			$site->write_self_closing_tag('input type="submit" name="match_enter_confirmed" value="Confirm to enter the new match" id="send" class="button"');
		} else
		{
			$site->write_self_closing_tag('input type="submit" name="match_edit_confirmed" value="Confirm to edit the match" id="send" class="button"');
		}
		$site->write_self_closing_tag('input type="submit" name="match_cancel" value="Cancel and change match data" id="cancel" class="button"');
		echo '</div>' . "\n";
		echo '</form>' . "\n";
		
		// done with the confirmation form
		$site->dieAndEndPage();
	}
	
	if ($confirmed === 2)
	{
		if (isset($_GET['enter']))
		{
			// match entering logic
			enter_match($team_id1, $team_id2, $team1_caps, $team2_caps, $timestamp, $duration);
		} elseif (isset($_GET['edit']))
		{
			edit_match($team_id1, $team_id2, $team1_caps, $team2_caps, $timestamp, $duration, $match_id);
		} elseif (isset($_GET['delete']))
		{
			delete_match($match_id);
		}

		
		// done with entering the match
		$site->dieAndEndPage();
	}
	
	// all necessary tasks are done
	
	function open_form()
	{
		global $site;
		global $match_id;
		
		echo '<form enctype="application/x-www-form-urlencoded" method="post" action="';
		if (isset($_GET['enter']))
		{
			echo '?enter">';
		} elseif (isset($_GET['edit']))
		{
			echo '?edit=' . urlencode($match_id) . '">';
		} else
		{
			echo '?delete=' . urlencode($match_id) . '">';
		}
	}
	
	
	function generate_confirmation_key()
	{
		global $site;
		global $randomkey_name;
		
		// generate hidden confirmation key
		$new_randomkey_name = $randomkey_name . microtime();
		$new_randomkey = $site->set_key($new_randomkey_name);
		$site->write_self_closing_tag('input type="hidden" name="key_name" value="'
									  . htmlspecialchars($new_randomkey_name) . '"');
		$site->write_self_closing_tag('input type="hidden" name="' . htmlspecialchars($randomkey_name) . '" value="'
									  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
	}
	
	
	function similarMatchEntered($newerMatches = true)
	{
		global $site;
		global $connection;
		
		// equal case should never happen
		$comparisonOperator = '>';
		if (!($newerMatches))
		{
			$comparisonOperator = '<=';
		}
		
		// similar match entered already?
		// strategy: ask for one match before the entered one and one after the one to be entered and do not let the database engine do the comparison
		$query = 'SELECT `id`,`timestamp`,`team1_teamid`,`team2_teamid`,`team1_points`,`team2_points`, `duration` FROM `matches`';
		$query .= ' WHERE (`timestamp`' . sqlSafeString($comparisonOperator) . sqlSafeStringQuotes($_POST['match_day'] . $_POST['match_time']);
		// sorting needed
		$query .= ') ORDER BY `timestamp` DESC';
		// only comparing nearest match in time
		$query .= ' LIMIT 0,1';
		
		if (!($result = @$site->execute_query('matches', $query, $connection)))
		{
			$site->dieAndEndPage('Unfortunately there seems to be a database problem and thus comparing timestamps (using operator '
								 . sqlSafeString($comparisonOperator) . ') of matches failed.');
		}
		
		// initialise values
		// casting the values to 0 is important
		// (a post variable having no value means it has to be set to 0 to successfully compare values here)
		$timestamp = '';
		$duration = (int) $_POST['duration'];
		$team_id1 = (int) $_POST['team_id1'];
		$team_id2 = (int) $_POST['team_id2'];
		$team_id1_matches = false;
		$team_id2_matches = false;
		$team1_points = (int) $_POST['team1_points'];
		$team2_points = (int) $_POST['team2_points'];
		$team1_points_matches = false;
		$team2_points_matches = false;
		
		while ($row = mysql_fetch_array($result))
		{
			// we can save comparisons using a helper variable
			$team_ids_swapped = false;
			$timestamp = $row['timestamp'];
			$duration_matches = (intval($row['duration'])) === $duration;
			$team_id1_matches = (intval($row['team1_teamid']) === $team_id1);
			if (!$team_id1_matches)
			{
				$team_ids_swapped = true;
				$team_id1_matches = (intval($row['team1_teamid']) === $team_id2);
			}
			
			if ($team_ids_swapped)
			{
				$team_id2_matches = (intval($row['team2_teamid']) === $team_id1);
			} else
			{
				$team_id2_matches = (intval($row['team2_teamid']) === $team_id2);
			}
			
			// use helper variable to save some comparisons of points
			if ($team_ids_swapped)
			{
				$team1_points_matches = (intval($row['team1_points']) === $team2_points);
				$team2_points_matches = (intval($row['team2_points']) === $team1_points);
			} else
			{
				$team1_points_matches = (intval($row['team1_points']) === $team1_points);
				$team2_points_matches = (intval($row['team2_points']) === $team2_points);
			}
			
		}
		mysql_free_result($result);
		
		// if similar match was found warn the user
		if ($team_id1_matches && $team_id2_matches && $team1_points_matches && $team2_points_matches && $duration_matches)
		{
			echo '<p>The nearest ';
			if ($newerMatches)
			{
				echo 'newer ';
			} else
			{
				echo 'older ';
			}
			echo ' match in the database is quite similar:</p>';
			// use the post data as much as possible instead of looking up the same data in the database
			echo '<p><strong>' . $timestamp . ' [' . $duration . '] </strong> ';
			
			$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($team_id1) . ' LIMIT 1';
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				$site->dieAndEndPage('Could not find out name of team #' . sqlSafeString($team_id1) . '.');
			}
			while ($row = mysql_fetch_array($result))
			{
				team_name_from_id($team_id1, htmlent($row['name']));
			}
			mysql_free_result($result);
			
			echo ' - ';
			$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($team_id2) . ' LIMIT 1';
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				$site->dieAndEndPage('Could not find out name of team #' . sqlSafeString($team_id2) . '.');
			}
			while ($row = mysql_fetch_array($result))
			{
				team_name_from_id($team_id2, htmlent($row['name']));
			}
			mysql_free_result($result);
			
			echo ' with result <strong>' . $team1_points . ' - ' . $team2_points . '</strong>.</p>';
			echo "\n";
			return true;
		}
		
		return false;
	}
	
	
	function permissionsCheck()
	{
		// check for permissions
		
		
		// initialise variables to false
		$allow_add_match = false;
		if (isset($_SESSION['allow_add_match']))
		{
			if (($_SESSION['allow_add_match']) === true)
			{
				$allow_add_match = true;
			}
		}
		
		$allow_edit_match = false;
		if (isset($_SESSION['allow_edit_match']))
		{
			if (($_SESSION['allow_edit_match']) === true)
			{
				$allow_edit_match = true;
			}
		}
		
		$allow_delete_match = false;
		if (isset($_SESSION['allow_delete_match']))
		{
			if (($_SESSION['allow_delete_match']) === true)
			{
				$allow_delete_match = true;
			}
		}
		
		// do the actual check, die if insufficient permissions
		if (!$allow_add_match && isset($_GET['enter']))
		{
			$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') have no permissions to enter new matches!');
		}
		if (!$allow_edit_match && isset($_GET['edit']))
		{
			$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') have no permissions to edit matches!');
		}
		if (!$allow_delete_match && isset($_GET['delete']))
		{
			$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') have no permissions to delete matches!');
		}
	}
	
	function sanityCheck(&$confirmed)
	{
		global $site;
		global $connection;
		global $randomkey_name;
		global $team_id1;
		global $team_id2;
		global $team1_caps;
		global $team2_caps;
		global $timestamp;
		global $duration;
		global $match_id;
		global $similarMatchFound;
		
		// sanitise match id
		if (isset($_GET['edit']))
		{
			$match_id = intval($_GET['edit']);
		}
		if (isset($_GET['delete']))
		{
			$match_id = intval($_GET['delete']);
		}
		
		// sanitise team variables
		
		if (isset($_POST['match_team_id1']))
		{
			$team_id1 = intval($_POST['match_team_id1']);
		} elseif (isset($_POST['team_id1']))
		{
			$team_id1 = intval($_POST['team_id1']);
		} else
		{
			$team_id1 = 0;
		}
		if ($team_id1 < 1)
		{
			$team_id1 = 0;
		}
		
		if (isset($_POST['match_team_id2']))
		{
			$team_id2 = intval($_POST['match_team_id2']);
		} elseif (isset($_POST['team_id2']))
		{
			$team_id2 = intval($_POST['team_id2']);
		} else
		{
			$team_id2 = 0;
		}
		if ($team_id2 < 1)
		{
			$team_id2 = 0;
		}
		
		
		// do the teams exist?
		
		// teams specified?
		if (!isset($_GET['delete']) && ($team_id1 > 0 && $team_id2 > 0))
		{
			$team_exists = 0;
			$query = 'SELECT COUNT(`id`) as `team_exists` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($team_id1) . ' LIMIT 1';
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				$site->dieAndEndPage('Could not find out name of team #' . sqlSafeString($team_id1) . '.');
			}
			while ($row = mysql_fetch_array($result))
			{
				$team_exits = intval($row['team_exists']);
			}
			mysql_free_result($result);
			if ($team_exits === 0)
			{
				echo '<p>Error: The specified team #1 does not exist</p>';
				$confirmed = 0;
			}
			
			// reset variable for team 2
			$team_exits = 0;
			$query = 'SELECT COUNT(`id`) as `team_exists` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($team_id2) . ' LIMIT 1';
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				$site->dieAndEndPage('Could not find out name of team #' . sqlSafeString($team_id2) . '.');
			}
			while ($row = mysql_fetch_array($result))
			{
				$team_exits = intval($row['team_exists']);
			}
			mysql_free_result($result);
			if ($team_exits === 0)
			{
				echo '<p>Error: The specified team #2 does not exist</p>';
				$confirmed = 0;
			}
			
			// teams are the same (and chosen by user)
			if ((($team_id1 > 0) && ($team_id2 > 0)) && ($team_id1 === $team_id2))
			{
				echo '<p class="error-msg">In order to be an official match, teams would have to be different!</p>';
				$confirmed = 0;
			}
		}
		
		// sanitise score variables
		
		if (isset($_POST['team1_points']))
		{
			$team1_caps = intval($_POST['team1_points']);
		} else
		{
			$team1_caps = 0;
		}
		
		if (isset($_POST['team2_points']))
		{
			$team2_caps = intval($_POST['team2_points']);
		} else
		{
			$team2_caps = 0;
		}
		
		// sanitise day and time variables
		
		if (isset($_POST['match_day']))
		{
			$match_day = $_POST['match_day'];
		} else
		{
			$match_day = date('Y-m-d');
		}
		
		if (isset($_POST['match_time']))
		{
			$match_time = $_POST['match_time'];
		} else
		{
			$match_time = date('H:i:s');
		}
		
		if (isset($_POST['match_day']) && isset($_POST['match_time']))
		{
			$timestamp = ($_POST['match_day']) . ' ' . sqlSafeString($_POST['match_time']);
		}
		
		// user wants to edit match data again
		if (isset($_POST['match_cancel']))
		{
			$confirmed = 0;
		}
		
		if (isset($_POST['$match_id']))
		{
			$match_id = intval($_POST['$match_id']);
		}
		
		
		if (isset($_POST['duration']))
		{
			$duration = intval($_POST['duration']);
		} else
		{
			$duration = 15;
		}
		
		
		// does the match exit?
		if (isset($match_id))
		{
			$query = ('SELECT `id` FROM `matches` WHERE `id`=' . sqlSafeStringQuotes($match_id));
			if (!($result = $site->execute_query('matches', $query, $connection)))
			{
				$site->dieAndEndPage('Could not find out id for team 1 given match id '
									 . sqlSafeString($match_id) . ' due to a sql problem!');
			}
			if ((intval(mysql_num_rows($result)) < 1))
			{
				// match did not exist!
				$confirmed = 0;
			}
		}
		
		
		// sanitise date and time specified
		
		// sanity checks regarding day format
		// sample day: 2009-12-15
		if (!(preg_match('/(2)(0|1|2|3|4|5|6|7|8|9){3,}-(0|1)(0|1|2|3|4|5|6|7|8|9)-(0|1|2|3)(0|1|2|3|4|5|6|7|8|9)/', $match_day)))
		{
			echo '<p>Please make sure your specified date is in correct format. Do not forget leading zeros.</p>' . "\n";
			$confirmed = (int) 0;
		}
		
		// sanity checks regarding time format
		// sample time: 15:21:35
		if (!(preg_match('/(0|1|2)([0-9]):([0-5])([0-9]):([0-5])([0-9])/', $match_time)))
		{
			echo '<p>Please make sure your specified time is in correct format. Do not forget leading zeros.</p>' . "\n";
			$confirmed = (int) 0;
		}
		
		// get the unix timestamp from the date and time
		if (!($specifiedTime = strtotime($match_day . ' ' . $match_time)))
		{
			echo '<p>Please make sure your specified date and time is valid!</p>' . "\n";
			$confirmed = (int) 0;
		}
		
		// look up if the day does exist in Gregorian calendar
		// checkdate expects order to be month, day, year
		if (!(checkdate(date('m', $specifiedTime), date('d', $specifiedTime), date('Y', $specifiedTime))))
		{
			echo '<p>Please make sure your specified date and time is a valid Gregorian date.</p>' . "\n";
			$confirmed = (int) 0;
		}
		
		// is match in the future?
		if (isset($timestamp))
		{
			$curTime = (int) strtotime('now');
			if ((((int) $specifiedTime) - $curTime) >= 0)
			{
				echo '<p class="error-msg">You tried to enter, edit or delete a match that would have been played in the future.';
				echo ' Only matches in the past can be entered, edited or deleted.</p>' . "\n";
				$confirmed = (int) 0;
			}
		}
		
		// is match older than 2 months?
		$eightWeeksAgo = (int) strtotime('now -8 weeks');
		if (((int) $specifiedTime) <= $eightWeeksAgo)
		{
				echo ('<p>You tried to enter, edit or delete a match that is older than 8 weeks.'
					  . 'Only matches played in the last 8 weeks can be entered, edited or deleted.</p>' . "\n");
				$confirmed = 0;
		}
		
		// check if there is already a match entered at that time
		// scores depend on the order, two matches done at the same time lead to undefined behaviour
		$query = 'SELECT `timestamp` FROM `matches` WHERE `timestamp`=' . sqlSafeStringQuotes($timestamp);
		if (!($result = @$site->execute_query('matches', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('Unfortunately there seems to be a database problem'
								 . ' and thus comparing timestamps (using equal operator) of matches failed.');
		}
		$rows = (int) mysql_num_rows($result);
		mysql_free_result($result);
		
		if ($rows > 0 && !isset($_GET['edit']) && !isset($_GET['delete']))
		{
			// go back to the first step of entering a match
			echo '<p class="error-msg">There is already a match entered at that exact time.';
			echo ' There can be only one finished at the same time because the scores depend on the order of the played matches.</p>' . "\n";
			// just warn them and let them enter it all again by hand
			echo '<p class="error-msg">Please enter the match with a different time.</p>' . "\n";
			echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?enter">' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="confirmed" value="0"');
			echo '</div>' . "\n";
			
			
			// pass the match values to the next page so the previously entered data can be set default for the new form
			show_form($team_id1, $team_id2, $team1_caps, $team2_caps, $duration, $readonly=false);
			
			echo '<div>';
			$site->write_self_closing_tag('input type="submit" name="match_cancel" value="Cancel and change match data" id="send" class="button l9"');
			echo '</div>' . "\n";
			echo '</form>' . "\n";
			$site->dieAndEndPage();
		}
		
		
		// random key validity check
		if ($confirmed > 1)
		{
			$new_randomkey_name = '';
			if (isset($_POST['key_name']))
			{
				$new_randomkey_name = html_entity_decode($_POST['key_name']);
			}
			$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
			
			if (!($randomkeysmatch))
			{
				echo '<p>The magic key did not match. It looks like you came from somewhere else. Going back to compositing mode.</p>';
				// reset the confirmed value
				$confirmed = 0;
			}
		}
		
		// check for similar match in database and warn user if at least one was found
		// skip warning if already warned (no infinite warning loop)
		if ($confirmed > 1 && !isset($_POST['similar_match']))
		{
			// find out if there are similar matches
			$similarMatchFound = false;
			$similarMatchFound = similarMatchEntered(true);
			if (!$similarMatchFound)
			{
				// look for a possible last show stopper
				$similarMatchFound = similarMatchEntered(false);
			} else
			{
				// add space between last similar match and the one probably following
				$site->write_self_closing_tag('br');
				
				// only call the function for user information, ignore result
				similarMatchEntered(false);
			}
			
			if ($similarMatchFound)
			{
				// ask for confirmation again and do not go ahead automatically
				$confirmed = 1;
			}
		}
		
		
		// no double confirmation about deletion - user saw confirmation step with $confirmed = 0 already
		if ($confirmed === 1 && isset($_GET['delete']))
		{
			$confirmed = 2;
		}
	}
?>