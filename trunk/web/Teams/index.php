<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require_once ('teams.inc');
	require realpath('../CMS/navi.inc');
	
	$site = new siteinfo();
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_team';
	$viewerid = (int) getUserID();
	
	
	$allow_edit_any_team_profile = false;
	
	
	$allow_reactivate_teams = false;
	if (isset($_SESSION['allow_reactivate_teams']) && ($_SESSION['allow_reactivate_teams'] == true))
	{
		$allow_reactivate_teams = true;
	}
	
	$team_description = '(no description)';
	if (isset($_GET['join']) || isset($_GET['edit']) || isset($_GET['profile']))
	{
		$query = 'SELECT ';
		if (!(isset($_GET['edit'])))
		{
			$query .= '`description`';
		} else
		{
			$query .= '`raw_description` AS `description`';
		}
		if (isset($_GET['edit']))
		{
			$query .= ',`logo_url`';
		}
		$query .= 'FROM `teams_profile` WHERE `teamid`=' . "'";
	}
	if (isset($_GET['join']))
	{
		$query .= sqlSafeString((int) $_GET['join']);
		
	}
	if (isset($_GET['edit']))
	{
		$query .= sqlSafeString((int) $_GET['edit']);
		
	}
	if (isset($_GET['profile']))
	{
		$query .= sqlSafeString((int) $_GET['profile']);
	}
	
	if (isset($_GET['join']) || isset($_GET['edit']) || isset($_GET['profile']))
	{
		$query .= "'" . ' LIMIT 0,1';
		if (!($result = @$site->execute_query('teams_profile', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		while($row = mysql_fetch_array($result))
		{
			$team_description = $row['description'];
			if (isset($_GET['edit']))
			{
				$logo_url = $row['logo_url'];
			}
		}
	}
	
	echo '<h1 class="teams">Teams</h1>';
	
	// sanity check to find out if a team does exist
	if (isset($_GET['profile']) || isset($_GET['edit']))
	{
		$profile = 0;
		if (isset($_GET['profile']))
		{
			$profile = (int) $_GET['profile'];
		} else
		{
			$profile = (int) $_GET['edit'];
		}
		
		if ($profile < 0)
		{
			echo '<div class="simple-paging"><a class="previous" href="./">overview</a></div>' . "\n";
			echo '<p class="message">This team does not exist!</p>';
			$site->dieAndEndPage('');
		}		
		
		if ($profile === 0)
		{
			echo '<div class="simple-paging"><a class="previous" href="./">overview</a></div>' . "\n";
			echo '<p class="message">The team id 0 is reserved for teamless players and thus no team with that id could ever exist.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		
		
		$query = 'SELECT `id` FROM `teams` WHERE `id`=' . "'" . sqlSafeString($profile) . "'" . ' LIMIT 1';
		if (!($result = @$site->execute_query('teams', $query, $connection)))
		{
			echo '<div class="simple-paging"><a class="previous" href="./">overview</a></div>' . "\n";
			$site->dieAndEndPage('<p class="message">It seems like the list of known teams can not be accessed for an unknown reason.</p>');
		}
		
		$rows = (int) mysql_num_rows($result);
		mysql_free_result($result);
		if ($rows ===0)
		{
			// someone tried to view the profile of a non existing user
			echo '<div class="simple-paging"><a class="previous" href="./">overview</a></div>' . "\n";
			echo '<p class="message">You tried to view or edit a non existing team!</p>';
			$site->dieAndEndPage('');
		}
	}
	
	// create new teams if requested
	if (isset($_GET['create']) && ($viewerid > 0))
	{
		if (isset($_POST['confirmed']) && (isset($_POST['edit_team_name']) || isset($_POST['team_description'])))
		{
			createTeam_step2();
		}
		
		createTeam_step1();
	}
	
	if (isset($_GET['reactivate']))
	{
		reactivateTeam();
	}
	
	if (isset($_GET['join']) && ($viewerid > 0))
	{
		joinTeam();
	}
	
	$team_leader_id = 0;
	$teamid = 0;
	if (isset($_GET['remove']) || isset($_GET['profile']) || isset($_GET['edit']) || isset($_GET['delete']))
	{
		
		// find out if the user accessing the page has editing permissions
		if (isset($_GET['profile']))
		{
			$teamid = (int) ($_GET['profile']);
		}
		if (isset($_GET['edit']))
		{
			$teamid = (int) ($_GET['edit']);
		}
		if (isset($_GET['delete']))
		{
			$teamid = (int) ($_GET['delete']);
		}
		if (isset($_GET['remove']))
		{
			// we don't know the team, find it out by tracing it using the playerid
			$playerid = (int) $_GET['remove'];
			$query = 'SELECT `teams`.`id` FROM `players`, `teams` WHERE `players`.`id`=';
			$query .= sqlSafeStringQuotes($playerid);
			$query .= ' AND `players`.`teamid` = `teams`.`id` LIMIT 0,1';
			if (!($result = @$site->execute_query('players, teams', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			while($row = mysql_fetch_array($result))
			{
				$teamid = (int) $row['id'];
			}
			mysql_free_result($result);
		}
		
		$query = 'SELECT `leader_playerid` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($teamid) . ' LIMIT 0,1';
		if (!($result = @$site->execute_query('teams', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		$rows = mysql_num_rows($result);
		if ($rows > 0)
		{
			while($row = mysql_fetch_array($result))
			{
				$team_leader_id = intval($row['leader_playerid']);
			}
		}
		
		$display_user_kick_from_team_buttons = false;
		if (($viewerid > 0) && (($viewerid === $team_leader_id) || (get_allow_kick_any_team_members() === true)))
		{
			// the user has permisson to kick a member from team
			$display_user_kick_from_team_buttons = true;
		}
		mysql_free_result($result);
	}
	
	// someone wants to delete a team
	if (isset($_GET['delete']))
	{
		deleteTeam($teamid);
	}
	
	// someone wants to edit a team
	if (isset($_GET['edit']))
	{
		editTeam($team_leader_id,$allow_edit_any_team_profile);
	}
	
	if (isset($_GET['remove']))
	{
		removePlayerFromTeam($playerid, $teamid, $team_leader_id, $display_user_kick_from_team_buttons);
	}
	
	// someone wants to look at a team profile
	if (isset($_GET['profile']))
	{
		showTeamProfile($team_description, $team_leader_id, $allow_edit_any_team_profile, $display_user_kick_from_team_buttons);
	}
	
	// someone wants to look at a team profile
	if (isset($_GET['activity_stats']))
	{
		showTeamActivityStats(intval($_GET['activity_stats']));
			
	}
	
	// someone wants to look at a team profile
	if (isset($_GET['opponent_stats']))
	{
		showTeamOpponentsStats(intval($_GET['opponent_stats']));
	}
	
	// nothing else wanted -> display overview
	echo '<div class="toolbar">';
	// reactivate button
	if ($allow_reactivate_teams)
	{
		echo '<a class="button" href="./?reactivate">Reactivate a team</a>' . "\n";
		$site->write_self_closing_tag('br');
	}
	
	// is player teamless?
	$player_teamless = false;
	// display a create team button to the teamless players viewing this page
	if ($viewerid > 0)
	{
		// this would also work without the viewerid switch but it would be one unneeded query,
		// thus avoid it due to performance reasons; slow sites are usually not liked
		$query = 'SELECT `teamid` FROM `players` WHERE `id`=' . sqlSafeStringQuotes($viewerid) . ' LIMIT 1';
		if (!($result = @$site->execute_query('players', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		while($row = mysql_fetch_array($result))
		{
			if (((int) $row['teamid']) === 0)
			{
				$player_teamless = true;
			}
		}
		mysql_free_result($result);
		
		if ($player_teamless)
		{
			echo '<a class="button" href="./?create">Create a new team</a>' . "\n";
		}
	}
	echo '</div>';
	
	echo '<div class="main-box">';
	// list the non deleted teams
	// example query: SELECT `teamid`,`name`, `score`, `member_count`,`activity`,`any_teamless_player_can_join` FROM `teams`, `teams_overview`
	// WHERE `teams`.`id` = `teams_overview`.`teamid` AND (`teams_overview`.`deleted`='0' OR `teams_overview`.`deleted`='1') ORDER BY `score`		
	$query = ('SELECT `teamid`,`teams`.`name` AS `name`,`leader_playerid`,'
	. ' (SELECT `name` FROM `players` WHERE `players`.`id`=`leader_playerid` LIMIT 1) AS `leader_name`,'
	. ' `score`,`num_matches_played`,`activity`, `member_count`,`any_teamless_player_can_join`,'
	. ' (SELECT `created` from `teams_profile` WHERE `teams_profile`.`teamid` = `teams`.`id` LIMIT 1) AS `created`'
	. ' FROM `teams`, `teams_overview`'
	. ' WHERE `teams`.`id` = `teams_overview`.`teamid`'
	. ' AND (`teams_overview`.`deleted`=' . "'" . '0' . "'"
	. ' OR `teams_overview`.`deleted`=' . "'" . '1' . "'" . ')'
	. ' ORDER BY `score` DESC');
	if ($result = @$site->execute_query('teams_overview', $query, $connection))
	{
		$rows = (int) mysql_num_rows($result);
		if ($rows === 0)
		{
			echo '<p>There are no teams in the database.</p>';
			$site->dieAndEndPageNoBox();
		}
		
		function display_teams($table_title, &$teams, &$invited_to_teams)
		{
			global $site;
			global $connection;
			global $viewerid;
			global $player_teamless;
			
			// do not display empty team tables
			if (count($teams) === 0)
			{
				return;
			}
			
			// id's have to be unique
			// append table name with lowercase
			$table_id = 'table_team_members_' . strtolower($table_title);
			// convert spaces to underscore
			$table_id = str_replace(' ', '_', $table_id);
			// use generated id
			echo '<table id="' . $table_id . '" class="big">' . "\n";
			// id no longer needed
			unset($table_id);
			
			echo '<caption>' . $table_title . '</caption>' . "\n";
			echo '<tr>' . "\n";
			echo '	<th>Name</th>' . "\n";
			echo '	<th>Score</th>' . "\n";
			echo '	<th>Matches</th>' . "\n";
			echo '	<th>Members</th>' . "\n";
			echo '	<th>Leader</th>' . "\n";
			echo '	<th>Activity</th>' . "\n";
			echo '	<th>Creation Date</th>' . "\n";
			if ($player_teamless)
			{
				echo '	<th>Allowed actions</th>' . "\n";
			}
			echo '</tr>' . "\n";
			
			foreach ($teams as &$team)
			{					
				echo '<tr class="teams_overview">' . "\n";
				echo '	<td><a href="./?profile=' . $team['teamid'] . '">';
				// team name empty?
				if (strcmp(($team['name']), '') === 0)
				{
					echo '(unnamed team)';
				} else
				{
					echo $team['name'] . '</a></td>' . "\n";
				}
				echo '	<td>';
				rankingLogo($team['score']);
				echo '</td>' . "\n";
				//echo '	<td>' . $team['num_matches_played'] . '</td>' . "\n";
				echo '	<td>';
				echo ' <a href="' .basepath() . 'Matches/?search_string=' . $team['name'] . '&search_type=team+name&search_result_amount=20&search=Search' . '">' . htmlent($team['num_matches_played']) . '</a>';
				echo ' <a href="' .basepath() . 'Teams/?opponent_stats=' . $team['teamid'] . '" title="Opponents stats"> &raquo;</a>';
				echo '</td>' . "\n";
				echo '	<td>' . $team['member_count'] . '</td>' . "\n";
				echo '	<td><a href="'. basepath() . 'Players/?profile=' . $team['leader_playerid'] . '">' . htmlent($team['leader_name']) . '</a></td>' . "\n";
				echo '	<td>' . $team['activity'] ;
				echo ' <a href="' .basepath() . 'Teams/?activity_stats=' . $team['teamid'] . '" title="Activity stats"> &raquo;</a>';
				echo '</td>' . "\n";
				echo '	<td>' . $team['created'] . '</td>' . "\n";
				if (($viewerid > 0) && ((int) $team['any_teamless_player_can_join'] === 1))
				{					
					// take care of potential database problems
					if ($player_teamless)
					{
						echo '	<td><a class="button" href="./?join=' . $team['teamid'] . '">Join team</a></td>' . "\n";
					}
				}
				else
				{
					// display empty row so there is always the same number of columns within a table
					if ($player_teamless)
					{
						if (in_array($team['teamid'],$invited_to_teams))
						{
							echo '	<td><a class="button" href="./?join=' . $team['teamid'] . '">Join team using invite</a></td>' . "\n";
						} else
						{
							echo '	<td></td>' . "\n";
						}
					}
				}
				echo '</tr>' . "\n";
			}
			unset($team);
			// no more players left to display
			echo '</table>' . "\n";
		}
		
		$active_teams = array(array());
		$inactive_teams = array(array());
		while ($row = mysql_fetch_array($result))
		{
			// classify team as active (at least 1 match in last 45 days) or not
			if (strcmp(substr($row['activity'],0,4), '0.00') === 0)
			{
				// inactive team
				$inactive_teams[$row['teamid']]['teamid'] = $row['teamid'];
				$inactive_teams[$row['teamid']]['name'] = $row['name'];
				$inactive_teams[$row['teamid']]['score'] = $row['score'];
				$inactive_teams[$row['teamid']]['num_matches_played'] = $row['num_matches_played'];
				$inactive_teams[$row['teamid']]['member_count'] = $row['member_count'];
				$inactive_teams[$row['teamid']]['leader_playerid'] = $row['leader_playerid'];
				$inactive_teams[$row['teamid']]['leader_name'] = $row['leader_name'];
				$inactive_teams[$row['teamid']]['activity'] = $row['activity'];
				$inactive_teams[$row['teamid']]['created'] = $row['created'];
				$inactive_teams[$row['teamid']]['any_teamless_player_can_join'] = $row['any_teamless_player_can_join'];
				
			} else
			{
				// active team
				$active_teams[$row['teamid']]['teamid'] = $row['teamid'];
				$active_teams[$row['teamid']]['name'] = $row['name'];
				$active_teams[$row['teamid']]['score'] = $row['score'];
				$active_teams[$row['teamid']]['num_matches_played'] = $row['num_matches_played'];
				$active_teams[$row['teamid']]['member_count'] = $row['member_count'];
				$active_teams[$row['teamid']]['leader_playerid'] = $row['leader_playerid'];
				$active_teams[$row['teamid']]['leader_name'] = $row['leader_name'];
				$active_teams[$row['teamid']]['activity'] = $row['activity'];
				$active_teams[$row['teamid']]['created'] = $row['created'];
				$active_teams[$row['teamid']]['any_teamless_player_can_join'] = $row['any_teamless_player_can_join'];
			}
		}
		mysql_free_result($result);
		
		$invited_to_teams = array();
		if ($player_teamless)
		{
			// is the player invited to the team?
			$query = 'SELECT `teamid` FROM `invitations` WHERE `invited_playerid`=' . sqlSafeStringQuotes($viewerid);
			// is the invitation expired?
			$query .= ' AND `expiration`>' . sqlSafeStringQuotes(date('Y-m-d H:i:s'));
			if (!($result = @$site->execute_query('invitations', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			$rows = (int) mysql_num_rows($result);
			if ($rows > 0)
			{
				while ($row = mysql_fetch_array($result))
				{
					$invited_to_teams[] = (int) $row['teamid'];
				}
			}
			mysql_free_result($result);
		}
		
		// first entry is always empty
		unset($active_teams[0]);
		unset($inactive_teams[0]);
		
		// display the teams, beginning with active ones
		display_teams('Active teams',$active_teams, $invited_to_teams);
		unset($active_teams);
		$site->write_self_closing_tag('br');
		display_teams('Inactive teams',$inactive_teams, $invited_to_teams);
		unset($inactive_teams);
		
		echo '</div>';
	}
	?>

</div>
</body>
</html>
