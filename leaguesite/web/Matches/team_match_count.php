<?php
	if (!isset($site))
	{
		die('This file is meant to be only included by other files!');
	}
	
   
	// backend functions
	function increase_total_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_overview` SET ';
		$query .= '`num_matches_played`=`num_matches_played`+' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_overview', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
		
		$query = 'UPDATE `teams_overview` SET `deleted`=' . sqlSafeStringQuotes('1');
		$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
		if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
		{
			$site->dieAndEndPage('Could not mark team with id ' . sqlSafeString($teamid) . ' as active!');
		}
	}
	
	function increase_won_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_profile` SET ';
		$query .= '`num_matches_won`=`num_matches_won`+' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_profile', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update win/play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
	}
	
	function increase_lost_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_profile` SET ';
		$query .= '`num_matches_lost`=`num_matches_lost`+' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_profile', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update win/play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
	}
	
	function increase_draw_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_profile` SET ';
		$query .= '`num_matches_draw`=`num_matches_draw`+' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_profile', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update win/play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
	}
	
	function decrease_total_match_count($teamid)
	{
		global $team_stats_changes;
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_overview` SET ';
		$query .= '`num_matches_played`=`num_matches_played`-' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_overview', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
		
		// the team did not participate in the newer version and its score did depend on the former match
		//  which should now no longer have a dependence on this team's score, thus mark the team score as being changed
		$team_stats_changes[$teamid] = '';
		
		// do not set team as inactive or something else because that would have to be computed
		// by considering a lot of matches and maintenance is supposed to do that
	}
	
	function decrease_won_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_profile` SET ';
		$query .= '`num_matches_won`=`num_matches_won`-' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_profile', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update win/play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
	}
	
	function decrease_lost_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_profile` SET ';
		$query .= '`num_matches_lost`=`num_matches_lost`-' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_profile', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update win/play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
	}
	
	function decrease_draw_match_count($teamid)
	{
		global $connection;
		global $site;
		
		$query = 'UPDATE `teams_profile` SET ';
		$query .= '`num_matches_draw`=`num_matches_draw`-' . sqlSafeStringQuotes('1');
		$query .= ' WHERE (`teamid`=' . sqlSafeStringQuotes($teamid) . ')';
		// only one team needs to be updated
		$query .= ' LIMIT 1';
		if (!($result = $site->execute_query('teams_profile', $query, $connection)))
		{
			unlock_tables($site, $connection);
			$site->dieAndEndPage('Could not update win/play count for team with id ' . sqlSafeString($teamid) . ' due to a sql problem!');
		}
	}
	
	function cmp_did_team_participate_at_all($team1_points_before, $team2_points_before,
											  $team1_points, $team2_points,
											  $team_id1_before, $team_id2_before,
											  $team_id1, $team_id2)
	{
		global $connection;
		global $site;
		
		if ($site->debug_sql())
		{
			echo '<hr>' . "\n";
			echo '<p>cmp_did_team_participate_at_all</p>' . "\n";
			echo '<p>$team1_points_before: ' . htmlentities($team1_points_before) . '</p>' . "\n";
			echo '<p>$team2_points_before: ' . htmlentities($team2_points_before) . '</p>' . "\n";
			echo '<p>$team1_points: ' . htmlentities($team1_points) . '</p>' . "\n";
			echo '<p>$team2_points: ' . htmlentities($team2_points) . '</p>' . "\n";
			echo '<p>$team_id1_before: ' . htmlentities($team_id1_before) . '</p>' . "\n";
			echo '<p>$team_id2_before: ' . htmlentities($team_id2_before) . '</p>' . "\n";
			echo '<p>$team_id1: ' . htmlentities($team_id1) . '</p>' . "\n";
			echo '<p>$team_id2: ' . htmlentities($team_id2) . '</p>' . "\n";
			echo '<hr>' . "\n";
		}
		
		// check if old team1 is still active in the new match version
		
		if (($team_id1_before !== $team_id1) && ($team_id1_before !== $team_id2))
		{
			// old team1 did participate in the older match version but not in the new version
			decrease_total_match_count($team_id1_before, $site, $connection);
			
			// update old team1 data
			if ($team1_points_before > $team2_points_before)
			{
				// old team1 won in the older version
				decrease_won_match_count($team_id1_before, $site, $connection);
			} else
			{
				if ($team1_points_before < $team2_points_before)
				{
					// old team1 lost in the older version
					decrease_lost_match_count($team_id1_before, $site, $connection);
				} else
				{
					// old team1 tied in the older version
					decrease_draw_match_count($team_id1_before, $site, $connection);
				}
			}
		}
	}
	
	function cmp_new_team_participated($team1_points_before, $team2_points_before,
									   $team1_points, $team2_points,
									   $team_id1_before, $team_id2_before,
									   $team_id1, $team_id2,
									   $team1_increased, $team2_increased)
	{
		global $connection;
		global $site;
		
		if ($site->debug_sql())
		{
			echo '<hr>' . "\n";
			echo '<p>cmp_new_team_participated</p>' . "\n";
			echo '<p>$team1_points_before: ' . htmlentities($team1_points_before) . '</p>' . "\n";
			echo '<p>$team2_points_before: ' . htmlentities($team2_points_before) . '</p>' . "\n";
			echo '<p>$team1_points: ' . htmlentities($team1_points) . '</p>' . "\n";
			echo '<p>$team2_points: ' . htmlentities($team2_points) . '</p>' . "\n";
			echo '<p>$team_id1_before: ' . htmlentities($team_id1_before) . '</p>' . "\n";
			echo '<p>$team_id2_before: ' . htmlentities($team_id2_before) . '</p>' . "\n";
			echo '<p>$team_id1: ' . htmlentities($team_id1) . '</p>' . "\n";
			echo '<p>$team_id2: ' . htmlentities($team_id2) . '</p>' . "\n";
			echo '<p>$team1_increased: ' . htmlentities($team1_increased) . '</p>' . "\n";
			echo '<p>$team2_increased: ' . htmlentities($team2_increased) . '</p>' . "\n";
			echo '<hr>' . "\n";
		}
		
		// we need a lock here to avoid increasing match stats for the same team twice
		// this may happen for both team1 and team2
		$team_increased = (int) 0;
		
		if (($team_id1 !== $team_id1_before) && ($team_id1 !== $team_id2_before)
			&& ($team_id1 !== $team1_increased) && ($team_id1 !== $team2_increased))
		{
			$team_increased = $team_id1;
			// new team1 played a match not counted yet
			increase_total_match_count($team_id1);
			
			// update new team1 data
			if ($team1_points > $team2_points)
			{
				// new team1 won
				increase_won_match_count($team_id1);
			} else
			{
				if ($team1_points < $team2_points)
				{
					// new team1 lost
					increase_lost_match_count($team_id1);
				} else
				{
					// new team1 tied
					increase_draw_match_count($team_id1);
				}
			}
		}
		return $team_increased;
	}
	
	function cmp_team_participated_change($team1_points_before, $team2_points_before,
										  $team1_points, $team2_points,
										  $team_id1_before, $team_id2_before,
										  $team_id1, $team_id2)
	{
		global $connection;
		global $site;
		
		// map old team id to new team id
		if ($team_id1_before === $team_id1)
		{
			if ($site->debug_sql())
			{
				echo '<br><p>teamid ' . htmlentities($team_id1) . ' mapped</p>';
				
				echo '<hr>' . "\n";
				echo '<p>Function cmp_team_participated_change called.</p>' . "\n";
				echo '<p>$team_id1_before: ' . htmlentities($team_id1_before) . '</p>' . "\n";
				echo '<p>$team_id1: ' . htmlentities($team_id1) . '</p>' . "\n";
				
				echo '<p>$team1_points_before: ' . htmlentities($team1_points_before) . '</p>' . "\n";
				echo '<p>$team2_points_before: ' . htmlentities($team2_points_before) . '</p>' . "\n";
				echo '<p>$team1_points: ' . htmlentities($team1_points) . '</p>' . "\n";
				echo '<p>$team2_points: ' . htmlentities($team2_points) . '</p>' . "\n";			
				echo '<hr>' . "\n";
				
			}
			if ($team1_points_before > $team2_points_before)
			{
				// team1 won in the older version
				if ($team1_points > $team2_points)
				{
					// team1 won also in the newer version -> nothing to do for team1
				} else
				{
					if ($team1_points < $team2_points)
					{
						// team1 lost in the newer version but won in the older version
						decrease_won_match_count($team_id1);
						increase_lost_match_count($team_id1);
					} else
					{
						// team1 tied in the newer version but won in the older version
						decrease_won_match_count($team_id1);
						increase_draw_match_count($team_id1);
					}
				}
			} else
			{
				if ($team1_points_before < $team2_points_before)
				{
					// team1 lost in the older match version
					if ($team1_points > $team2_points)
					{
						// team1 won in the newer version
						decrease_lost_match_count($team_id1);
						increase_won_match_count($team_id1);
					} else
					{
						if ($team1_points < $team2_points)
						{
							// team1 lost in the older version and in the newer version
						} else
						{
							// team1 lost in the older version but tied in the newer version
							decrease_lost_match_count($team_id1);
							increase_draw_match_count($team_id1);
						}
					}
				} else
				{
					// team1 tied in the older match version
					
					if ($team1_points > $team2_points)
					{
						// team1 won in the newer version
						decrease_draw_match_count($team_id1);
						increase_won_match_count($team_id1);
					} else
					{
						if ($team1_points < $team2_points)
						{
							// team1 tied in the older version and lost in the newer version
							decrease_draw_match_count($team_id1);
							increase_lost_match_count($team_id1);
						} else
						{
							// team1 tied in the older version and also tied in the newer version -> nothing to do
						}
					}
				}
			}
			return true;
		}
		return false;
	}
		
	function update_team_match_edit($team1_points_before, $team2_points_before,
									$team1_points, $team2_points,
									$team_id1_before, $team_id2_before,
									$team_id1, $team_id2)
	{
		global $connection;
		global $site;
		
		if ($site->debug_sql())
		{
			echo '<hr>' . "\n";
			echo '<p>Updating win, draw, loose count of teams (edit case).</p>' . "\n";
			echo '<p>$team1_points_before: ' . htmlentities($team1_points_before) . '</p>' . "\n";
			echo '<p>$team2_points_before: ' . htmlentities($team2_points_before) . '</p>' . "\n";
			echo '<p>$team1_points: ' . htmlentities($team1_points) . '</p>' . "\n";
			echo '<p>$team2_points: ' . htmlentities($team2_points) . '</p>' . "\n";
			echo '<p>$team_id1_before: ' . htmlentities($team_id1_before) . '</p>' . "\n";
			echo '<p>$team_id2_before: ' . htmlentities($team_id2_before) . '</p>' . "\n";
			echo '<p>$team_id1: ' . htmlentities($team_id1) . '</p>' . "\n";
			echo '<p>$team_id2: ' . htmlentities($team_id2) . '</p>' . "\n";
			echo '<hr>' . "\n";
		}
		
		// check if old team1 is still active in the new match version
		cmp_did_team_participate_at_all($team1_points_before, $team2_points_before,
										 $team1_points, $team2_points,
										 $team_id1_before, $team_id2_before,
										 $team_id1, $team_id2);
		// swap the team orders to apply the same algorithm to old team2
		cmp_did_team_participate_at_all($team2_points_before, $team1_points_before,
										 $team2_points, $team1_points,
										 $team_id2_before, $team_id1_before,
										 $team_id2, $team_id1);
		
		// check for new teams, to give them credit for new match
		// initialise locking variables
		$team1_added = (int) 0;
		$team2_added = (int) 0;
		
		$team1_added = cmp_new_team_participated($team1_points_before, $team2_points_before,
												 $team1_points, $team2_points,
												 $team_id1_before, $team_id2_before,
												 $team_id1, $team_id2,
												 $team1_added, $team2_added);
		$team2_added = cmp_new_team_participated($team2_points_before, $team1_points_before,
												 $team1_points, $team2_points,
												 $team_id2_before, $team_id1_before,
												 $team_id1, $team_id2,
												 $team1_added, $team2_added);
		
		if (!(($team1_added !== 0) && ($team2_added !== 0)))
		{
			if ($team1_added !== 0)
			{
				$team3_swap = (int) $team1_added;
				$team1_added = $team2_added;
				$team2_added = $team3_swap;
				unset($team3_swap);
			}
			$team1_added = cmp_new_team_participated($team1_points_before, $team2_points_before,
													 $team2_points, $team1_points,
													 $team_id1_before, $team_id2_before,
													 $team_id2, $team_id1,
													 $team1_added, $team2_added);
			
			if (!(($team1_added !== 0) && ($team2_added !== 0)))
			{
				if ($team1_added !== 0)
				{
					$team3_swap = (int) $team1_added;
					$team1_added = $team2_added;
					$team2_added = $team3_swap;
					unset($team3_swap);
				}
				
				cmp_new_team_participated($team2_points_before, $team1_points_before,
										  $team2_points, $team1_points,
										  $team_id2_before, $team_id1_before,
										  $team_id2, $team_id1,
										  $team1_added, $team2_added);
			}
		}
		unset($team1_added);
		unset($team2_added);
		// update match stats for team1 in case old team1 = new team1
		
		$number_teams_mapped = (int) 0;
		if ($site->debug_sql())
		{
			echo "call1";
		}
		// new team and old teams in old order
		if (cmp_team_participated_change($team1_points_before, $team2_points_before,
										 $team1_points, $team2_points,
										 $team_id1_before, $team_id2_before,
										 $team_id1, $team_id2))
		{
			$number_teams_mapped = $number_teams_mapped + 1;
		}
		
		if ($site->debug_sql())
		{
			echo "call2";
		}
		// swap old teams, leave new teams in old order
		if (cmp_team_participated_change($team2_points_before, $team1_points_before,
										 $team1_points, $team2_points,
										 $team_id2_before, $team_id1_before,
										 $team_id1, $team_id2))
		{
			$number_teams_mapped = $number_teams_mapped + 1;
		}
		
		if (!($number_teams_mapped >= 2))
		{
			if ($site->debug_sql())
			{
				echo "call3";
			}
		}
			// old teams in old order, swap new teams
			if (cmp_team_participated_change($team1_points_before, $team2_points_before,
											 $team2_points, $team1_points,
											 $team_id1_before, $team_id2_before,
											 $team_id2, $team_id1))
			{
				$number_teams_mapped = $number_teams_mapped + 1;
			}
			if (!($number_teams_mapped >= 2))
			{
				if ($site->debug_sql())
				{
					echo "call4";
				}
				// swap old and new teams
				if (cmp_team_participated_change($team2_points_before, $team1_points_before,
												 $team2_points, $team1_points,
												 $team_id2_before, $team_id1_before,
												 $team_id2, $team_id1))
				{
					$number_teams_mapped = $number_teams_mapped + 1;
				}
			}
		unset($number_teams_mapped);
	}
?>