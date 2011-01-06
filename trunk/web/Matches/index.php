<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require ('../CMS/navi.inc');
	require_once (dirname(dirname(__FILE__)) . '/Seasons/seasons.inc');
	
	echo '<h1 class="matches">Matches</h1>';
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_matches';
	$viewerid = (int) getUserID();
	
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

	
	function get_table_checksum($site, $connection)
	{
		$query = 'CHECKSUM TABLE `matches`';
		
		if (!($result = @$site->execute_query('matches', $query, $connection)))
		{
			// a severe problem with the table exists
			$site->dieAndEndPageNoBox('Checksum of the matches could not be generated');
		}
		
		$checksum = '';
		while($row = mysql_fetch_array($result))
		{
			$checksum = $row['Checksum'];
		}
		
		return $checksum;
	}
	
	function setTableUnchanged($site, $connection)
	{
		$_SESSION['checksum_matches'] = get_table_checksum($site, $connection);
	}
	
	function team_name_from_id($id, $name)
	{
		echo '<a href="../Teams/?profile=' . ((int) $id) . '">' . $name . '</a>';
	}
	
	function player_name_from_id($id, $name)
	{
		echo '<a href="../Players/?profile=' . ((int) $id) . '">' . $name . '</a>';
	}
	
	
	if (isset($_GET['enter']) || isset($_GET['edit']) || isset($_GET['delete']))
	{
		echo '<div class="simple-paging">';
		echo '<a class="button" href="./">overview</a>' . "\n";
			echo '</div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
	
		
		require('match_form.php');
		$site->dieAndEndPage();
//		include_once('match_list_changing_logic.php');
//		// all the operations requested have been dealt with
//		$site->dieAndEndPage();
	}
	
	
	echo '<div class="simple-paging">';
	if (isset($_GET['search']))
	{
		echo '<a class="button" href="./">overview</a>' . "\n";
	}
	if ($allow_add_match)
	{
		if (isset($_GET['search']))
		{
		}
		echo '<a class="button" href="./?enter">Enter a new match</a>' . "\n";
	}
	
	echo '</div>';
	echo '<div class="main-box">' . "\n";
	
	// form letting search for team name or time
	// this form is considered not to be dangerous, thus no key checking at all and also using the get method
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="get" action="./" class="search_bar simpleform">' . "\n";
	
	// input string
	echo '<div class="formrow"><label for="match_search_string">Search for:</label> ' . "\n";
	echo '<span>';
	if (isset($_GET['search']))
	{
		$site->write_self_closing_tag('input type="text" title="use * as wildcard" id="match_search_string" name="search_string" value="'
									  . $_GET['search_string'] . '"');
	} else
	{
		$site->write_self_closing_tag('input type="text" title="use * as wildcard" id="match_search_string" name="search_string"');
	}
	echo '</span></div> ' . "\n";
	
	// looking for either team name or time
	echo '<div class="formrow"><label for="match_search_type">result type:</label> ' . "\n";
	echo '<span><select id="match_search_type" name="search_type">';
	
	// avoid to let the user enter a custom table column at all costs
	// only let them switch between team name and time search
	
	// search for team name by default
	$search_type = '';
	$search_team = false;
	$search_time = false;
	
	if (isset($_GET['search_type']))
	{
		switch ($_GET['search_type'])
		{
			case 'team': $search_team = true; break;
			case 'time': $search_time = true; break;
			default: $search_team = true;
		}
	}
	
	echo '<option';
	if ($search_team)
	{
		$search_type = 'team';
		echo ' selected="selected"';
	}
	echo '>team name</option>';
	
	echo '<option';
	if ($search_time)
	{
		$search_type = 'time';
		echo ' selected="selected"';
	}
	echo '>time</option>';
	
	echo '</select></span>';
	
	// how many resulting rows does the user wish?
	// assume 200 by default
	$num_results = 200;
	if (isset($_GET['search']))
	{
		if (isset($_GET['search_result_amount']))
		{
			if ($_GET['search_result_amount'] > 0)
			{
				// cast result to int to avoid SQL injections
				$num_results = (int) $_GET['search_result_amount'];
				// a little try against denial of service by limiting displayed amount per page
				if ($num_results > 3200)
				{
					$num_results = 3200;
				}
			}
		}
	}
	echo ' <label for="match_search_result_amount">Entries:</label> ';
	echo '<span><select id="match_search_result_amount" name="search_result_amount">';
	echo '<option';
	if ($num_results === 200)
	{
		echo ' selected="selected"';
	}
	echo '>200</option>';
	echo '<option';
	if ($num_results === 400)
	{
		echo ' selected="selected"';
	}
	echo '>400</option>';
	echo '<option';
	if ($num_results === 800)
	{
		echo ' selected="selected"';
	}
	echo '>800</option>';
	echo '<option';
	if ($num_results === 1600)
	{
		echo ' selected="selected"';
	}
	echo '>1600</option>';
	echo '<option';
	if ($num_results === 3200)
	{
		echo ' selected="selected"';
	}
	echo '>3200</option>';
	echo '</select></span>';
	echo '</div> ' . "\n";
	
	echo '<div style="display:inline">';
	$site->write_self_closing_tag('input type="submit" name="search" value="Search" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	// end search toolbar
	
	if (isset($_GET['search']))
	{
		// search for nothing by default
		$search_expression = '';
		if (isset($_GET['search_string']))
		{
			$search_expression = $_GET['search_string'];
		}
		// people like to use * as wildcard
		$search_expression = str_replace('*', '%', $search_expression);
		
		if (strcmp('', $search_expression) === 0)
		{
			$search_expression = '%';
		}
	}
	
	if (isset($_GET['search']))
	{
		// outer select
		$query = 'SELECT * FROM';
		$query .= ' (SELECT `matches`.`timestamp`';
	} else
	{
		$query = 'SELECT `matches`.`timestamp`';
	}
	// get name of team 1
	$query .= ',(SELECT `name` FROM `teams` WHERE `matches`.`team1_teamid`=`teams`.`id` LIMIT 1) AS `team1_name`';
	// get name of team 2
	$query .= ',(SELECT `name` FROM `teams` WHERE `matches`.`team2_teamid`=`teams`.`id` LIMIT 1) AS `team2_name`';
	// also need the id's for quick links to team profiles
	$query .= ',`matches`.`team1_teamid`,`matches`.`team2_teamid`';
	// the rest of the needed data
	$query .= ',`matches`.`team1_points`,`matches`.`team2_points`,`matches`.`playerid`';
	$query .= ',`players`.`name` AS `playername`,`matches`.`id`, `matches`.`duration`';
	// the tables in question
	$query .= ' FROM `matches`,`players` WHERE `players`.`id`=`matches`.`playerid`';
	if (isset($_GET['search']))
	{
		// Every derived table must have its own alias
		$query .= ') AS `t1`';
		// now do the search thing
		if ($search_team)
		{
			// team name search
			$query .= 'WHERE `team1_name` LIKE ' . sqlSafeStringQuotes($search_expression);
			$query .= ' OR `team2_name` LIKE ' . sqlSafeStringQuotes($search_expression);
		} else
		{
			// timestamp search
			$query .= 'WHERE `timestamp` LIKE ' . sqlSafeStringQuotes($search_expression . '%');
		}
	}
	
	// newest matches first please
	$query .= ' ORDER BY `timestamp` DESC ';
	// limit the output to the requested rows to speed up displaying
	$query .= 'LIMIT ';
	
	$view_range = (int) 0;
	// the "LIMIT 0,200" part of query means only the first 200 entries are received
	// the range of shown matches is set by the GET variable i
	if (isset($_GET['i']))
	{
		if (((int) $_GET['i']) > 0)
		{
			$view_range = (int) $_GET['i'];
			$query .=  $view_range . ',';
		} else
		{
			// force write 0 for value 0 (speed saving due to no casting to string)
			// and 0 for negative values (security: DBMS error handling prevention)
			$query .= '0,';
		}
	} else
	{
		// no special value set -> write 0 for value 0 (speed)
		$query .= '0,';
	}
	// limit the number of displayed rows regarding the user's wish
	$query .= sqlSafeString($num_results + 1);
	
	if (!($result = @$site->execute_query('matches', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of matches could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	$show_next_matches_button = false;
	// more than wished match entries per page available in total
	if ($rows > $num_results)
	{
		$show_next_matches_button = true;
	}
	if ($rows === (int) 0)
	{
		echo '<p>No matches have been played yet.</p>' . "\n";
		setTableUnchanged($site, $connection);
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	echo '<table id="table_matches_played" class="big">' . "\n";
	echo '<caption>Matches played</caption>' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Time</th>' . "\n";
	echo '	<th>Teams</th>' . "\n";
	echo '	<th>Result</th>' . "\n";
	echo '	<th>last mod by</th>' . "\n";
	// show edit/delete links in a new table column if user has permission to use these
	// adding matches is not done from within the table
	if ($allow_edit_match || $allow_delete_match)
	{
		echo '	<th>Allowed actions</th>' . "\n";
	}
	echo '</tr>' . "\n\n";
	
	// display message overview
	$matchid_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$matchid_list[$id]['timestamp'] = $row['timestamp'];
		$matchid_list[$id]['duration'] = $row['duration'];
		$matchid_list[$id]['team1_name'] = $row['team1_name'];
		$matchid_list[$id]['team2_name'] = $row['team2_name'];
		$matchid_list[$id]['team1_teamid'] = $row['team1_teamid'];
		$matchid_list[$id]['team2_teamid'] = $row['team2_teamid'];
		$matchid_list[$id]['team1_points'] = $row['team1_points'];
		$matchid_list[$id]['team2_points'] = $row['team2_points'];
		$matchid_list[$id]['playerid'] = $row['playerid'];
		$matchid_list[$id]['playername'] = $row['playername'];
		$matchid_list[$id]['id'] = $row['id'];
	}
	unset($matchid_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
	
	// are more than 200 rows in the result?
	if ($show_next_matches_button)
	{
		// only show 200 messages, not 201
		// NOTE: array_pop would not work on a resource (e.g. $result)
		array_pop($matchid_list);
	}
	
	// walk through the array values
	foreach($matchid_list as $match_entry)
	{
		echo '<tr class="matches_overview">' . "\n";
		echo '<td>';
		echo $match_entry['timestamp'] . ' [' . $match_entry['duration'] . ']';
		echo '</td>' . "\n" . '<td>';
		
		// get name of first team
		team_name_from_id($match_entry['team1_teamid'], $match_entry['team1_name']);
		
		// seperator showing that opponent team will be named soon
		echo ' - ';
		
		// get name of second team
		team_name_from_id($match_entry['team2_teamid'], $match_entry['team2_name']);
		
		// done with the table field, go to next field
		echo '</td>' . "\n" . '<td>';
		
		echo htmlentities($match_entry['team1_points']);
		echo ' - ';
		echo htmlentities($match_entry['team2_points']);
		echo '</td>' . "\n";
		
		echo '<td>';
		// get name of first team
		player_name_from_id($match_entry['playerid'], $match_entry['playername']);		
		echo '</td>' . "\n";

		
		// show allowed actions based on permissions
		if ($allow_edit_match || $allow_delete_match)
		{
			echo '<td>';
			if ($allow_edit_match)
			{
				echo '<a class="button" href="./?edit=' . htmlspecialchars($match_entry['id']) . '">Edit match result</a>';
			}
			if ($allow_edit_match && $allow_delete_match)
			{
				echo ' ';
			}
			if ($allow_delete_match)
			{
				echo '<a class="button" href="./?delete=' . htmlspecialchars(urlencode($match_entry['id'])) . '">Delete match</a>';
			}
			echo '</td>' . "\n";
		}
		
		
		echo '</tr>' . "\n\n";
	}
	unset($matchid_list);
	unset($match_entry);
	
	// no more matches to display
	echo '</table>' . "\n";
	
	// look up if next and previous buttons are needed to look at all entries in overview
	if ($show_next_matches_button || ($view_range !== (int) 0))
	{
		// browse previous and next entries, if possible
		echo "\n" . '<p>'  . "\n";
		
		if ($view_range !== (int) 0)
		{
			echo '	<a href="./?i=';
			
			echo ((int) $view_range)-$num_results;
			if (isset($_GET['search']))
			{
				echo '&amp;search';
				if (isset($_GET['search_string']))
				{
					echo '&amp;search_string=' . htmlspecialchars($_GET['search_string']);
				}
				if (isset($_GET['search_type']))
				{
					echo '&amp;search_type=' . htmlspecialchars($_GET['search_type']);
				}
				if (isset($num_results))
				{
					echo '&amp;search_result_amount=' . strval($num_results);
				}
			}
			
			echo '">Previous matches</a>' . "\n";
		}
		if ($show_next_matches_button)
		{
			
			echo '	<a href="./?i=';
			
			echo ((int) $view_range)+$num_results;
			if (isset($_GET['search']))
			{
				echo '&amp;search';
				if (isset($_GET['search_string']))
				{
					echo '&amp;search_string=' . htmlspecialchars($_GET['search_string']);
				}
				if (isset($_GET['search_type']))
				{
					echo '&amp;search_type=' . htmlspecialchars($_GET['search_type']);
				}
				if (isset($num_results))
				{
					echo '&amp;search_result_amount=' . strval($num_results);
				}
			}
			
			echo '">Next matches</a>' . "\n";
		}
		echo '</p>' . "\n";
	}
		
	if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		setTableUnchanged($site, $connection);
	}
?>
</div>
</body>
</html>