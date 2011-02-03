<?php
	// set the date and time
	date_default_timezone_set($site->used_timezone());
	
	// find out if maintenance is needed (compare old date in plain text file)
	$today = date('d.m.Y');

	$file = (dirname(__FILE__)) . '/maintenance.txt';
	
	// siteinfo class used all the time
	if (!(isset($site)))
	{
		require_once ((dirname(dirname(__FILE__)) . '/siteinfo.php'));
		$site = new siteinfo();
	}
	
	if (!(isset($connection)))
	{
		// database connection is used during maintenance
		$connection = $site->connect_to_db();
	}
	
	function unlock_tables_maint()
	{
		global $site;
		global $connection;
		
		$query = 'UNLOCK TABLES';
		if (!($site->execute_query('all!', $query, $connection)))
		{
			$site->dieAndEndPage('Unfortunately unlocking tables failed. This likely leads to an access problem to database!');
		}
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
	
	$query = 'LOCK TABLES `misc_data` WRITE, `teams` WRITE, `teams_overview` WRITE, `teams_permissions` WRITE, `teams_profile` WRITE';
	$query .= ', `players` WRITE, `players_profile` WRITE, `visits` WRITE, `messages_users_connection` WRITE, `messages_storage` WRITE';
	$query .= ', `countries` WRITE, `matches` WRITE';
	if (!($result = @$site->execute_query('all!', $query, $connection)))
	{
		unlock_tables_maint();
		$site->dieAndEndPage('Unfortunately locking the matches table failed and thus entering the match was cancelled.');
	}
	$query = 'SET AUTOCOMMIT = 0';
	if (!($result = @$site->execute_query('all!', $query, $connection)))
	{
		unlock_tables_maint();
		$site->dieAndEndPage('Trying to deactivate autocommit failed.');
	}
	
	// find out when last maintenance happened
	$last_maintenance = '00.00.0000';
	$query = 'SELECT `last_maintenance` FROM `misc_data` LIMIT 1';
	// execute query
	if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
	{
		unlock_tables_maint();
		$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
	}
	
	if ((int) mysql_num_rows($result) === 0)
	{
		mysql_free_result($result);
		// first maintenance run in history
		$query = 'INSERT INTO `misc_data` (`last_maintenance`) VALUES (' . sqlSafeStringQuotes($today) . ')';
		// execute query
		if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
		{
			unlock_tables_maint();
			$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
		}
	} else
	{
		// read out the date of last maintenance
		while ($row = mysql_fetch_array($result))
		{
			$last_maintenance = $row['last_maintenance'];
		}
		mysql_free_result($result);
	}
	
	// was maintenance done today?
	if (strcasecmp($last_maintenance, $today) == 0)
	{
		// update team activity of teams in array $teams (defined in file that includes this file)
		if (isset($teams))
		{
			update_activity($teams);
		}
		
		// nothing else to do
		// stop silently
		unlock_tables_maint();
		
	}	else 
	{ // do the maintenance
	
		
		$maint = new maintenance();
		$maint->do_maintenance($site, $connection);
		update_activity();
	}
	
	function update_activity($teamid=false)
	{
		global $site;
		global $connection;
		
		// update team activity
		if ($teamid === false)
		{
			$num_active_teams = 0;
			// find out the number of active teams
			$query = 'SELECT COUNT(*) AS `num_teams` FROM `teams_overview` WHERE `deleted`<>' . sqlSafeStringQuotes('2');
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: could not find out number of active teams.');
			}
			while ($row = mysql_fetch_array($result))
			{
				$num_active_teams = (int) $row['num_teams'] -1;
			}
			
			$query = 'SELECT `teamid` FROM `teams_overview` WHERE `deleted`<>' . sqlSafeStringQuotes('2');
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: could not find out id list of active teams.');
			}
			$teamid = array();
			while ($row = mysql_fetch_array($result))
			{
				$teamid[] = (int) $row['teamid'];
			}
			mysql_free_result($result);
		} else
		{
			$num_active_teams = count($teamid) -1;
		}
		
		$team_activity45 = array();
		$timestamp = strtotime('-45 days');
		$timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
		// find out how many matches each team did play
		for ($i = 0; $i <= $num_active_teams; $i++)
		{
			$query = 'SELECT COUNT(*) as `num_matches` FROM `matches` WHERE `timestamp`>' . sqlSafeStringQuotes($timestamp);
			$query .= ' AND (`team1_teamid`=' . sqlSafeStringQuotes($teamid[$i]) . ' OR `team2_teamid`=' . sqlSafeStringQuotes($teamid[$i]) . ')';
			// execute query
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: could not find out how many matches team with id '
									 . $teamid[$i]
									 . ' played in the last 45 days');
			}
			while ($row = mysql_fetch_array($result))
			{
				$team_activity45[$i] = intval($row['num_matches']);
			}
			
			$team_activity45[$i] = ($team_activity45[$i] / 45);
			// number_format may round but it is not documented (behaviour may change), force doing it
			$team_activity45[$i] = number_format(round($team_activity45[$i], 2), 2, '.', '');
		}
		
		$team_activity90 = array();
		$timestamp = strtotime('-90 days');
		$timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
		// find out how many matches each team did play
		for ($i = 0; $i <= $num_active_teams; $i++)
		{
			$query = 'SELECT COUNT(*) as `num_matches` FROM `matches` WHERE `timestamp`>' . sqlSafeStringQuotes($timestamp);
			$query .= ' AND (`team1_teamid`=' . sqlSafeStringQuotes($teamid[$i]) . ' OR `team2_teamid`=' . sqlSafeStringQuotes($teamid[$i]) . ')';
			// execute query
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: could not find out how many matches team with id '
									 . $teamid[$i]
									 . ' played in the last 90 days');
			}
			while ($row = mysql_fetch_array($result))
			{
				$team_activity90[$i] = intval($row['num_matches']);
			}
			
			$team_activity90[$i] = ($team_activity90[$i] / 90);
			// number_format may round but it is not documented (behaviour may change), force doing it
			$team_activity90[$i] = number_format(round($team_activity90[$i], 2), 2, '.', '');
		}
		
		for ($i = 0; $i <= $num_active_teams; $i++)
		{
			$team_activity45[$i] .= ' (' . $team_activity90[$i] . ')';
			
			// update activity entry
			$query = 'Update `teams_overview` SET `activity`=' . sqlSafeStringQuotes($team_activity45[$i]);
			$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($teamid[$i]);
			// execute query, ignore result
			@$site->execute_query('teams_overview', $query, $connection);
		}
		unset($teamid);
		unset($team_activity45);
		unset($team_activity90);
	}
	
	// set up a class to have a unique namespace
	class maintenance
	{		
		function cleanup_teams($two_months_in_past)
		{
			global $settings;
			global $site;
			global $connection;
			
			
			// teams cleanup
			if (!$settings->maintain_teams_not_matching_anymore())
			{
				// in settings it was specified not to maintain inactive teams
				echo '<p>Skipped maintaining inactive teams (by config option)!</p>';
				return;
			}
			
			
			$query = 'SELECT `teamid`, `member_count`, `deleted` FROM `teams_overview`';
			$query .= ' WHERE `deleted`<>' . sqlSafeStringQuotes('2');
			// execute query
			if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: getting list of teams with deleted not equal 2 (2 means deleted team) failed.');
			}
			
			// 6 months long inactive teams will be deleted during maintenance
			// inactive is defined as the team did not match 6 months
			$six_months_in_past = strtotime('-6 months');
			$six_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $six_months_in_past);			
			
			// walk through results
			while ($row = mysql_fetch_array($result))
			{
				// the id of current team investigated
				$curTeam = $row['teamid'];
				
				// is the team new?
				$curTeamNew = ((int) $row['deleted'] === 0);
				
				$query = 'SELECT `timestamp` FROM `matches`';
				if ((int) $row['deleted'] === 3)
				{
					// re-activated team from admins will only last 2 months without matching
					$query .= ' WHERE `timestamp`>' . sqlSafeStringQuotes($two_months_in_past);
				} else
				{
					// team marked as active (deleted === 1) has 6 months to match before being deleted
					$query .= ' WHERE `timestamp`>' . sqlSafeStringQuotes($six_months_in_past);
				}
				$query .= ' AND (`team1_teamid`=' . sqlSafeStringQuotes($curTeam);
				$query .= ' OR `team2_teamid`=' . sqlSafeStringQuotes($curTeam) . ')';
				// only one match is sufficient to be considered active
				$query .= ' LIMIT 1';
				// execute query
				if (!($result_matches = @$site->execute_query('matches', $query, $connection)))
				{
					unlock_tables_maint();
					$site->dieAndEndPage('MAINTENANCE ERROR: getting list of recent matches from teams failed.');
				}
				
				// mark the team as inactive by default
				$cur_team_active = false;
				// walk through results
				while ($row_matches = mysql_fetch_array($result_matches))
				{
					// now we know the current team is active
					$cur_team_active = true;
				}
				mysql_free_result($result_matches);
				
				if (!$cur_team_active && !$settings->maintain_teams_not_matching_anymore_players_still_loggin_in())
				{
					$query = ('SELECT `players`.`id` AS `active_player`'
							  . ' FROM `players`,`players_profile`'
							  . ' WHERE `teamid` = ' . sqlSafeStringQuotes($curTeam)
							  . ' AND `players_profile`.`last_login`>' . sqlSafeStringQuotes($six_months_in_past)
							  . ' AND `players`.`id`=`players_profile`.`playerid`'
							  // only 1 active player is enough not to deactivate the team
							  . ' LIMIT 1');
					// execute query
					if (!($result_active_players = @$site->execute_query('matches', $query, $connection)))
					{
						unlock_tables_maint();
						$site->dieAndEndPage('MAINTENANCE ERROR: getting list of recent logged in player from team'
											 . sqlSafeStringQuotes($curTeam)
											 . ' failed.');
					}
					if ((int) mysql_num_rows($result_active_players) > 0)
					{
						// at least one player logged in during the last 6 months
						// in settings it was specified to count the current team as active then
						$cur_team_active = true;
					}
					mysql_free_result($result_active_players);
				}
				
				if (((int) $row['member_count']) === 0)
				{
					// no members in team implies team inactive
					$cur_team_active = false;
				}
				
				// if team not active and is not new, delete it for real (do not mark as deleted but actually do it!)
				if (!$cur_team_active && $curTeamNew)
				{
					// delete (for real) the new team
					$query = 'DELETE FROM `teams` WHERE `id`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams', $query, $connection);
					$query = 'DELETE FROM `teams_overview` WHERE `teamid`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams_overview', $query, $connection);
					$query = 'DELETE FROM `teams_permissions` WHERE `teamid`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams_permissions', $query, $connection);
					$query = 'DELETE FROM `teams_profile` WHERE `teamid`=' . "'" . ($curTeam) . "'";
					// execute query, ignore result
					@$site->execute_query('teams_profile', $query, $connection);						
				}
				
				// if team not active but is not new, mark it as deleted
				if (!$cur_team_active && !$curTeamNew)
				{
					// delete team data:
					
					// delete description
					$query = 'UPDATE `teams_profile` SET description=' . "'" . "'";
					$query .= ', logo_url=' . "'" . "'";
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					// only one player needs to be updated
					$query .= ' LIMIT 1';
					// execute query, ignore result
					@$site->execute_query('teams_profile', $query, $connection);	
					
					// mark the team as deleted
					$query = 'UPDATE `teams_overview` SET deleted=' . sqlSafeStringQuotes('2');
					$query .= ', member_count=' . sqlSafeStringQuotes('0');
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					// only one team with that id in database (id is unique identifier)
					$query .= ' LIMIT 1';
					// execute query, ignoring result
					@$site->execute_query('teams_overview', $query, $connection);
					
					// mark who was where, to easily restore an unwanted team deletion
					$query = 'UPDATE `players` SET `last_teamid`=' . sqlSafeStringQuotes($curTeam);
					$query .= ', `teamid`=' . sqlSafeStringQuotes('0');
					$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($curTeam);
					if (!($result_update = @$site->execute_query('players', $query, $connection)))
					{
						unlock_tables_maint();
						$site->dieAndEndPage();
					}
				}
			}
		}
		
		function do_maintenance($site, $connection)
		{
			global $settings;
			global $today;
			
			if (!isset($today) ) $today = date('d.m.Y');
			
			echo '<p>Performing maintenance...</p>';
			
			$settings = new maintenance_settings();
			
			// flag stuff
			$query = 'SELECT `id` FROM `countries` WHERE `id`=' . sqlSafeStringQuotes('1');
			if (!($result = @$site->execute_query('countries', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPageNoBox('Could not find out if country with id 1 does exist in database');
			}
			$insert_entry = false;
			if (!(mysql_num_rows($result) > 0))
			{
				$insert_entry = true;
			}
			mysql_free_result($result);
			
			if ($insert_entry)
			{
				$query = 'INSERT INTO `countries` (`id`,`name`, `flagfile`) VALUES (';
				$query .= sqlSafeStringQuotes('1') . ',';
				$query .= sqlSafeStringQuotes('here be dragons') . ',';
				$query .= sqlSafeStringQuotes('') . ')';
				if (!($result = @$site->execute_query('countries', $query, $connection)))
				{
					unlock_tables_maint();
					$site->dieAndEndPageNoBox('Could not insert reserved country with name '
											  . sqlSafeStringQuotes('here be dragons')
											  . ' into database');
				}
			}
			
			$dir = dirname(dirname(dirname(__FILE__))) . '/Flags';
			$countries = array();
			if ($handle = opendir($dir))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file != '.' && $file != '..' && $file != '.svn' && $file != '.DS_Store')
					{
						$countries[] = $file;
					}
				}
				closedir($handle);
			}
			foreach($countries as &$one_country)
			{
				$flag_name_stripped = str_replace('Flag_of_', '', $one_country);
				$flag_name_stripped = str_replace('.png', '', $flag_name_stripped);
				$flag_name_stripped = str_replace('_', ' ', $flag_name_stripped);
				$query = 'SELECT `flagfile` FROM `countries` WHERE `name`=' . sqlSafeStringQuotes($flag_name_stripped);
				if (!($result = @$site->execute_query('countries', $query, $connection)))
				{
					unlock_tables_maint();
					$site->dieAndEndPageNoBox('Could not check if flag '
											  . sqlSafeStringQuotes($one_country)
											  . ' does exist in database');
				}
				$update_country = false;
				$insert_entry = false;
				if (!(mysql_num_rows($result) > 0))
				{
					$update_country = true;
					$insert_entry = true;
				}
				if (!$update_country)
				{
					while ($row = mysql_fetch_array($result))
					{
						if (!(strcmp($row['flagfile'], $one_country) === 0))
						{
							$update_country = true;
						}
					}
				}
				mysql_free_result($result);
				
				if ($update_country)
				{
					if ($insert_entry)
					{
						$query = 'INSERT INTO `countries` (`name`, `flagfile`) VALUES (';
						$query .= sqlSafeStringQuotes($flag_name_stripped) . ',';
						$query .= sqlSafeStringQuotes($one_country) . ')';
					} else
					{
						$query = 'UPDATE `countries` SET `flagfile`=' . sqlSafeStringQuotes($one_country);
						$query .= 'WHERE `name`=' . sqlSafeStringQuotes($flag_name_stripped);
					}
					
					// do the changes
					if (!($result = @$site->execute_query('countries', $query, $connection)))
					{
						unlock_tables_maint();
						$site->dieAndEndPageNoBox('Could update or insert country entry for '
												  . sqlSafeStringQuotes($one_country)
												  . ' in database.');
					}
				}
			}
			
			
			// date of 2 months in past will help during maintenance
			$two_months_in_past = strtotime('-3 months');
			$two_months_in_past = strftime('%Y-%m-%d %H:%M:%S', $two_months_in_past);
			
			// clean teams first
			// if team gets deleted players will be maintained later
			$this->cleanup_teams($two_months_in_past);
			
			
			// maintain players now
			
			// get player id of teamless players that have not been logged-in in the last 2 months
			$query = 'SELECT `playerid` FROM `players`, `players_profile`';
			$query .= ' WHERE `players`.`teamid`=' . sqlSafeStringQuotes('0');
			$query .= ' AND `players`.`status`=' . sqlSafeStringQuotes('active');
			$query .= ' AND `players_profile`.`playerid`=`players`.`id`';
			$query .= ' AND `players_profile`.`last_login`<' . sqlSafeStringQuotes($two_months_in_past);
			
			// execute query
			if (!($result = @$site->execute_query('players, players_profile', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: getting list of 3 months long inactive players failed.');
			}
			
			// store inactive players in an array
			$inactive_players = Array();
			while($row = mysql_fetch_array($result))
			{
				$inactive_players[] = $row['playerid'];
			}
			mysql_free_result($result);
			
			// handle each inactive player seperately
			foreach ($inactive_players as $one_inactive_player)
			{
				// delete account data:
				$this->deleteAccount($one_inactive_player, $two_months_in_past);
			}
			
			echo '<p>Maintenance performed successfully.</p>';
				
			// update maintenance date
			$query = 'UPDATE `misc_data` SET `last_maintenance`=' . sqlSafeStringQuotes($today);
			// execute query
			if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: Can not get last maintenance data from database.');
			}
			unlock_tables_maint();
			// do not die in maintenance, let the caller do the job
		}
		
		function deleteAccount($one_inactive_player, $two_months_in_past)
		{
			global $site;
			global $connection;	
		
			// delete account data:
						
			// user entered comments
			$query = 'UPDATE `players_profile` SET `user_comment`=' . "'" . "'";
			$query .= ', logo_url=' . "'" . "'";
			$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($one_inactive_player);
			// only one player needs to be updated
			$query .= ' LIMIT 1';
			// execute query, ignore result
			@$site->execute_query('players_profile', $query, $connection);
			
			// visits log (ip-addresses and host data)
			$query = 'DELETE FROM `visits` WHERE `playerid`=' . sqlSafeStringQuotes($one_inactive_player);
			@$site->execute_query('visits', $query, $connection);
			
			// private messages connection
			
			// get msgid first!
			$query = 'SELECT `msgid` FROM `messages_users_connection` WHERE `playerid`=' . sqlSafeStringQuotes($one_inactive_player);
			// execute query
			if (!($result = @$site->execute_query('players, players_profile', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: getting private msgid list of inactive players failed.');
			}
			
			$msg_list = Array();
			while($row = mysql_fetch_array($result))
			{
				$msg_list[] = $row['msgid'];
			}
			mysql_free_result($result);				
			
			// now delete the connection to mailbox
			$query = 'DELETE FROM `messages_users_connection` WHERE `playerid`=' . sqlSafeStringQuotes($one_inactive_player);
			@$site->execute_query('messages_users_connection', $query, $connection);				
			
			// delete private messages itself, in case no one else has the message in mailbox
			foreach ($msg_list as $msgid)
			{
				$query = 'SELECT `msgid` FROM `messages_users_connection` WHERE `msgid`=' . sqlSafeStringQuotes($msgid);
				$query .= ' AND `playerid`<>' . sqlSafeStringQuotes($one_inactive_player);
				// we only need to know whether there is more than zero rows the result of query
				$query .= ' LIMIT 1';
				if (!($result = @$site->execute_query('messages_users_connection', $query, $connection)))
				{
					unlock_tables_maint();
					$site->dieAndEndPage('MAINTENANCE ERROR: finding out whether actual private messages can be deleted failed.');
				}
				$rows = (int) mysql_num_rows($result);
				mysql_free_result($result);
				
				if ($rows < ((int) 1))
				{
					// delete actual message
					$query = 'DELETE FROM `messages_storage` WHERE `id`=' . sqlSafeStringQuotes($msgid);
					@$site->execute_query('messages_storage', $query, $connection);								
				}
			}
			unset($msgid);
			
			// mark account as deleted
			$query = 'UPDATE `players` SET `status`=' . sqlSafeStringQuotes('deleted');
			$query .= ' WHERE `id`=' . sqlSafeStringQuotes($one_inactive_player);
			// and again only one player needs to be updated
			$query .= ' LIMIT 1';
			@$site->execute_query('players', $query, $connection);
			
			// FIXME: if user marked deleted check if he was leader of a team
			$query = 'SELECT `id` FROM `teams` WHERE `leader_playerid`=' . sqlSafeStringQuotes($one_inactive_player);
			// only one player was changed and thus only one team at maximum needs to be updated
			$query .= ' LIMIT 1';
			// execute query
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				unlock_tables_maint();
				$site->dieAndEndPage('MAINTENANCE ERROR: finding out if inactive player was leader of a team failed.');
			}
			
			// walk through results
			$member_count_modified = false;
			while ($row = mysql_fetch_array($result))
			{
				// set the leader to 0 (no player)
				$query = 'Update `teams` SET `leader_playerid`=' . sqlSafeStringQuotes('0');
				$query .= ' WHERE `leader_playerid`=' . sqlSafeStringQuotes($one_inactive_player);
				// execute query, ignore result
				@$site->execute_query('teams', $query, $connection);
				
				// update member count of team
				$member_count_modified = true;
				$teamid = $row['id'];
				$query = 'UPDATE `teams_overview` SET `member_count`=(SELECT COUNT(*) FROM `players` WHERE `players`.`teamid`=';
				$query .= sqlSafeStringQuotes($teamid) . ') WHERE `teamid`=';
				$query .= sqlSafeStringQuotes($teamid);
				// execute query, ignore result
				@$site->execute_query('teams', $query, $connection);
			}
			mysql_free_result($result);
			
			if ($member_count_modified)
			{
				// during next maintenance the team that has no leader would be deleted
				// however the time between maintenance can be different
				// and the intermediate state could confuse users
				// thus force the team maintenance again
				$this->cleanup_teams($site, $connection, $two_months_in_past);
			}
		}
		
	}
?>