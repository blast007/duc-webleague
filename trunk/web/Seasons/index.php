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
	
	
	$connection = $site->connect_to_db();
	$viewerid = (int) getUserID();
	
	$allow_add_season = false;
	if (isset($_SESSION['allow_add_season']))
	{
		if (($_SESSION['allow_add_season']) === true)
		{
			$allow_add_season = true;
		}
	}
	
	$allow_edit_season = false;
	if (isset($_SESSION['allow_edit_season']))
	{
		if (($_SESSION['allow_edit_season']) === true)
		{
			$allow_edit_season = true;
		}
	}
	
	$allow_delete_season = false;
	if (isset($_SESSION['allow_delete_match']))
	{
		if (($_SESSION['allow_delete_match']) === true)
		{
			$allow_delete_season = true;
		}
	}

	
	function get_table_checksum($site, $connection)
	{
		$query = 'CHECKSUM TABLE `seasons`';
		
		if (!($result = @$site->execute_query('seasons', $query, $connection)))
		{
			// a severe problem with the table exists
			$site->dieAndEndPageNoBox('Checksum of the seasons could not be generated');
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
		$_SESSION['checksum_seasons'] = get_table_checksum($site, $connection);
	}
	
	function team_name_from_id($id, $name)
	{
		echo '<a href="../Teams/?profile=' . ((int) $id) . '">' . $name . '</a>';
	}
	
	function player_name_from_id($id, $name)
	{
		echo '<a href="../Players/?profile=' . ((int) $id) . '">' . $name . '</a>';
	}
	
	if (isset($_POST['create']) && $allow_add_season)
	{
		
		// sanitise day variables
		
		if (isset($_POST['startdate']))
		{
			$startdate = $_POST['startdate'];
		}		
		if (isset($_POST['enddate']))
		{
			$enddate = $_POST['enddate'];
		}
		
		if (!(preg_match('/(2)(0|1|2|3|4|5|6|7|8|9){3,}-(0|1)(0|1|2|3|4|5|6|7|8|9)-(0|1|2|3)(0|1|2|3|4|5|6|7|8|9)/', $startdate)))
		{
			echo '<p>Please make sure your specified date is in correct format. Do not forget leading zeros.</p>' . "\n";	
		} elseif (!(preg_match('/(2)(0|1|2|3|4|5|6|7|8|9){3,}-(0|1)(0|1|2|3|4|5|6|7|8|9)-(0|1|2|3)(0|1|2|3|4|5|6|7|8|9)/', $enddate)))
		{
			echo '<p>Please make sure your specified date is in correct format. Do not forget leading zeros.</p>' . "\n";
		} else 
		{
			$query = ('INSERT INTO `seasons` (`startdate`, `enddate` '
					  . ', `active`, `points_win`, `points_draw`, `points_lost`)'
					  . ' VALUES (' . sqlSafeStringQuotes($startdate) . ', ' . sqlSafeStringQuotes($enddate)
					  . ', true, 4, 2, 1 )' );
			
			if (!($result = $site->execute_query('seasons', $query, $connection)))
			{
				unlock_tables();
				$site->dieAndEndPage('The season could not be created due to a sql problem!');
			}
		}
	}
	//Season activation
	if (isset($_GET['activate']) && $allow_edit_season)
	{
		
		$seasonid =  intval($_GET['activate']);
		$query = ('UPDATE `seasons` SET `active` = true WHERE `id` =' . sqlSafeStringQuotes($seasonid));
			
		if (!($result = $site->execute_query('seasons', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('The season could not be activated due to a sql problem!');
		}	

		recalculate_season($seasonid);
	}
	
	//Season deactivation
	if (isset($_GET['deactivate']) && $allow_edit_season)
	{
		$seasonid =  intval($_GET['deactivate']);
		$query = ('UPDATE `seasons` SET `active` = false WHERE `id` =' . sqlSafeStringQuotes($seasonid));
			
		if (!($result = $site->execute_query('seasons', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('The season could not be activated due to a sql problem!');
		}		
	}
	
	//Season deactivation
	if (isset($_GET['delete']) && $allow_delete_season)
	{
		$seasonid =  intval($_GET['delete']);
		$query = ('DELETE FROM `seasons` WHERE `id` =' . sqlSafeStringQuotes($seasonid));
			
		if (!($result = $site->execute_query('seasons', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('The season could not be deleted due to a sql problem!');
		}		
	}
	
	//Season recalculation results
	if (isset($_GET['recalc']) && $allow_edit_season)
	{
		$seasonid = intval($_GET['recalc']);
		recalculate_season($seasonid);
	}	
	
	echo '<h1 class="seasons">Seasons</h1>' . "\n";
	
	echo '<div class="main-box">';
	
	
	if ($allow_add_season)
	{
		
		// form letting search for team name or time
	// this form is considered not to be dangerous, thus no key checking at all and also using the get method
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="post" action="./" class="simpleform">' . "\n";
	
	// input string
	echo '<div class="formrow"><label for="season_startdate">Start date:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="Start of the season" id="season_startdate" name="startdate" value="' . date('Y-m-d') . '"');
	
	echo '</span></div> ' . "\n";
	
	echo '<div class="formrow"><label for="season_enddate">End date:</label> ' . "\n";
	
	echo '<span>';
		//by default is 45days length
		$site->write_self_closing_tag('input type="text" title="End of the season" id="season_enddate" name="enddate" value="' . date('Y-m-d',time()+ 86400 * 45) . '"' );
	
	echo '</span></div> ' . "\n";
	echo '<div class="formrow">';
	$site->write_self_closing_tag('input type="submit" name="create" value="Create a new season" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	}
	
	
	
	$query = 'SELECT s.id, s.startdate, s.enddate, s.active '
	.', team_1, t1.name AS team_1_name, sr1.score AS team_1_score, sr1.num_matches_played AS team_1_matches '
	.',	team_2, t2.name AS team_2_name, sr2.score AS team_2_score, sr2.num_matches_played AS team_2_matches '
	.',	team_3, t3.name AS team_3_name, sr3.score AS team_3_score, sr3.num_matches_played AS team_3_matches ';

	$query .= ' FROM `seasons` s  ';
	$query .= ' LEFT JOIN `teams` t1 ON (t1.id = s.team_1) ';
	$query .= ' LEFT JOIN `teams` t2 ON (t2.id = s.team_2) ';
	$query .= ' LEFT JOIN `teams` t3 ON (t3.id = s.team_3) ';
	$query .= ' LEFT JOIN `seasons_results` sr1 ON (sr1.teamid = t1.id AND sr1.seasonid = s.id) ';
	$query .= ' LEFT JOIN `seasons_results` sr2 ON (sr2.teamid = t2.id AND sr2.seasonid = s.id) ';
	$query .= ' LEFT JOIN `seasons_results` sr3 ON (sr3.teamid = t3.id AND sr3.seasonid = s.id) ';
	
	
	//show not active onces only if editing allowed
	if (!$allow_edit_season) {
		$query .= ' WHERE s.active =  true' ;
	}
	// newest seasons first please
	$query .= ' GROUP BY s.id ORDER BY s.startdate DESC ';

	
	if (!($result = @$site->execute_query('seasons', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of seasons could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	
	if ($rows === (int) 0)
	{
		echo '<p>No seasons have been played yet.</p>' . "\n";
		setTableUnchanged($site, $connection);
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	echo '<table id="table_seasons" class="big">' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Date</th>' . "\n";
	echo '	<th>1st</th>' . "\n";
	echo '	<th>2nd</th>' . "\n";
	echo '	<th>3rd</th>' . "\n";
	// show edit/delete links in a new table column if user has permission to use these
	// adding matches is not done from within the table
	if ($allow_edit_season || $allow_delete_season)
	{
		echo '	<th>Allowed actions</th>' . "\n";
	}
	echo '</tr>' . "\n\n";
	
	// display message overview
	$seasonid_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$seasonid_list[$id]['startdate'] = $row['startdate'];
		$seasonid_list[$id]['enddate'] = $row['enddate'];
		$seasonid_list[$id]['active'] = $row['active'];
		$seasonid_list[$id]['team_1'] = $row['team_1'];
		$seasonid_list[$id]['team_2'] = $row['team_2'];
		$seasonid_list[$id]['team_3'] = $row['team_3'];
		$seasonid_list[$id]['team_1_name'] = $row['team_1_name'];
		$seasonid_list[$id]['team_2_name'] = $row['team_2_name'];
		$seasonid_list[$id]['team_3_name'] = $row['team_3_name'];
		$seasonid_list[$id]['team_1_score'] = $row['team_1_score'];
		$seasonid_list[$id]['team_2_score'] = $row['team_2_score'];
		$seasonid_list[$id]['team_3_score'] = $row['team_3_score'];
		$seasonid_list[$id]['team_1_matches'] = $row['team_1_matches'];
		$seasonid_list[$id]['team_2_matches'] = $row['team_2_matches'];
		$seasonid_list[$id]['team_3_matches'] = $row['team_3_matches'];		
		$seasonid_list[$id]['id'] = $row['id'];
		
		
	}
	unset($seasonid_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
	
		
	// walk through the array values
	foreach($seasonid_list as $season_entry)
	{
		echo '<tr class="seasons_overview">' . "\n";
		echo '<td>';
		echo '<a href="/Season/?season_id=' . $season_entry['id'] . '">' . $season_entry['startdate'].' - '.$season_entry['enddate'] . '</a>' ;
		echo '</td>' . "\n" . '<td>';
		team_name_from_id( $season_entry['team_1'],  $season_entry['team_1_name']);
		team_season_results( $season_entry['team_1_score'],  $season_entry['team_1_matches']);
		echo '</td>' . "\n" . '<td>';
		team_name_from_id( $season_entry['team_2'],  $season_entry['team_2_name']);
		team_season_results( $season_entry['team_2_score'],  $season_entry['team_2_matches']);
		echo '</td>' . "\n" . '<td>';
		team_name_from_id( $season_entry['team_3'],  $season_entry['team_3_name']);
		team_season_results( $season_entry['team_3_score'],  $season_entry['team_3_matches']);
		echo '</td>' . "\n";

		
		// show allowed actions based on permissions
		if ($allow_edit_season || $allow_delete_season)
		{
			echo '<td>';
			if ($allow_edit_season)
			{
				if ($season_entry['active'] == true )
				{
					echo '<a class="button" href="./?deactivate=' . htmlspecialchars($season_entry['id']) . '">Deactivate season</a> ';
					echo '<a class="button" href="./?recalc=' . htmlspecialchars($season_entry['id']) . '">Recalculate season</a> ';
				} else
				echo '<a class="button" href="./?activate=' . htmlspecialchars($season_entry['id']) . '">Activate season</a> ';
				
			}			
			if ($allow_delete_season)
			{
				echo '<a class="button" href="./?delete=' . htmlspecialchars(urlencode($season_entry['id'])) . '" 
				onclick="return confirm(\'Are you sure you want to delete this season?\')">Delete season</a>';
			}
			echo '</td>' . "\n";
		}
		
		
		echo '</tr>' . "\n\n";
	}
	unset($seasonid_list);
	unset($season_entry);
	
	// no more matches to display
	echo '</table>' . "\n";
	
			
	if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		setTableUnchanged($site, $connection);
	}
	
	function team_season_results($score, $num_matches)
	{
		if ($num_matches > 0)
		{
			echo '<span class="team-results">' . $score . ' ('. $num_matches . ')</span>';			
		}
	}
	
	
	function lock_tables()
	{
		global $site;
		global $connection;
		global $tables_locked;
		
		$tables_locked = true;
		
		// concurrent access could alter the table while much of the data inside the table is recalculated
		// as most of the data in table depends on each other we must not access it in a concurrent way
		
		// any call of unlock_tables(...) will unlock the table
		$query = 'LOCK TABLES `seasons` WRITE, `teams` WRITE, `seasons_results` WRITE, `matches` WRITE';
		
		
		if (!($result = @$site->execute_query('seasons, seasons_results, matches, teams', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('Unfortunately locking the matches table failed and thus altering the list of matches was cancelled.');
		}
		
		// innoDB may neeed autcommit = 0
		$query = 'SET AUTOCOMMIT = 0';
		if (!($result = @$site->execute_query('all!', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('Trying to deactivate autocommit failed.');
		}
	}
	
	
	function unlock_tables()
	{
		global $site;
		global $connection;
		
		global $tables_locked;
		
		if ($tables_locked)
		{
			$query = 'UNLOCK TABLES';
			if (!($site->execute_query('all!', $query, $connection)))
			{
				$site->dieAndEndPage('Unfortunately unlocking tables failed. This likely leads to an access problem to database!');
			}
			$tables_locked = false;
			$query = 'COMMIT';
			if (!($site->execute_query('all!', $query, $connection)))
			{
				$site->dieAndEndPage('Unfortunately committing changes failed!');
			}
			$query = 'SET AUTOCOMMIT = 1';
			if (!($result = @$site->execute_query('all!', $query, $connection)))
			{
				$site->dieAndEndPage('Trying to activate autocommit failed.');
			}
		}
	}
	
	
	
	
?>
</div>
</div>
</body>
</html>