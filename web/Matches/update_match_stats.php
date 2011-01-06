<?php
	if (!isset($site))
	{
		die("This file is meant to be only included by other files!");
	}
	
	function update_match_stats_entered($team_id1, $team_id2, $team1_points, $team2_points, $site, $connection)
	{
		// increase match count for teams that participated
		$query = 'UPDATE `teams_overview` SET `num_matches_played`=`num_matches_played`+1';
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($team_id1) . ' OR `teamid`=' . sqlSafeStringQuotes($team_id2) . ')';
		if (!($result = $site->execute_query('teams_overview', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('The match count for the teams with id'
								 . sqlSafeString($team_id1) . ' and ' . sqlSafeString($team_id2) . ' could not be updated due to a sql problem!');
		}
		
		// mark both participating teams as active
		$query = ('UPDATE `teams_overview` SET `deleted`=' . sqlSafeStringQuotes('1')
				  . ' WHERE `teamid`=' . sqlSafeStringQuotes($team_id1) . ' OR `teamid`=' . sqlSafeStringQuotes($team_id2)
				  . ' LIMIT 2');
		if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
		{
			$site->dieAndEndPage('Could not mark team with id ' . sqlSafeString($teamid) . ' as active!');
		}
		
		
		// increase match win count for teams that participated
		if ($team1_points > $team2_points)
		{
			// team 1 won
			$query = 'UPDATE `teams_profile` SET `num_matches_won`=`num_matches_won`+1';
			$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($team_id1);
			if (!($result = $site->execute_query('teams_profile', $query, $connection)))
			{
				unlock_tables($site, $connection);
				$site->dieAndEndPage('The match win count for team ' . sqlSafeString($team_id1) . ' could not be updated due to a sql problem!');
			}
			
			// team 2 lost
			$query = 'UPDATE `teams_profile` SET `num_matches_lost`=`num_matches_lost`+1';
			$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($team_id2);
			if (!($result = $site->execute_query('teams_profile', $query, $connection)))
			{
				unlock_tables($site, $connection);
				$site->dieAndEndPage('The match lose count for team ' . sqlSafeString($team_id2) . ' could not be updated due to a sql problem!');
			}
		}
		
		if ($team1_points < $team2_points)
		{
			// team 2 won
			$query = 'UPDATE `teams_profile` SET `num_matches_won`=`num_matches_won`+1';
			$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($team_id2);
			if (!($result = $site->execute_query('teams_profile', $query, $connection)))
			{
				unlock_tables($site, $connection);
				$site->dieAndEndPage('The match win count for team ' . sqlSafeString($team_id2) . ' could not be updated due to a sql problem!');
			}
			
			// team 1 lost
			$query = 'UPDATE `teams_profile` SET `num_matches_lost`=`num_matches_lost`+1';
			$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($team_id1);
			if (!($result = $site->execute_query('teams_profile', $query, $connection)))
			{
				unlock_tables($site, $connection);
				$site->dieAndEndPage('The match lose count for team ' . sqlSafeString($team_id1) . ' could not be updated due to a sql problem!');
			}
		}
		
		// match entered ended in a draw
		if (((int) $team1_points) === ((int) $team2_points))
		{
			$query = 'UPDATE `teams_profile` SET `num_matches_draw`=`num_matches_draw`+1';
			$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($team_id1) . ' OR `teamid`=' . sqlSafeStringQuotes($team_id2) . ')';
			if (!($result = $site->execute_query('teams_profile', $query, $connection)))
			{
				unlock_tables($site, $connection);
				$site->dieAndEndPage('The match draw count for the teams with id'
									 . sqlSafeString($team_id1) . ' and ' . sqlSafeString($team_id2) . ' could not be updated due to a sql problem!');
			}
			
		}
	}
?>