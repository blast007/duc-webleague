<?php
	if (!isset($site))
	{
		die('This file is meant to be only included by other files!');
	}
//get seasons active when that match was played 
	function update_seasons($match_time)
	{ 
		global $connection;
		global $site;
		
		$query = ('SELECT id FROM seasons WHERE active = true '
		. ' AND startdate < ' .  sqlSafeStringQuotes($match_time) 
		. ' AND enddate > ' . sqlSafeStringQuotes($match_time) );
		
		if (!($result = @$site->execute_query('seasons', $query, $connection)))
		{
			$site->dieAndEndPage('Could not get season active that time due to a sql problem!');
		}
		while ($row = mysql_fetch_array($result))
		{ //recalculate those seasons results;
			recalculate_season($row['id']);
		}
	}
	
	function recalculate_season($seasonid) 
	{
		global $connection;
		global $site;
		
		//default values
		$pointswin = 4; //default values
		$pointslost = 1;
		$pointsdraw = 2;
		
		//lock_tables();
		//get all teams which played in that season
		$query = ('SELECT DISTINCT teams.id, seasons.startdate, seasons.enddate '
		. ',seasons.points_win, seasons.points_lost, seasons.points_draw FROM teams '
		. ' LEFT JOIN matches ON ( matches.team1_teamid = teams.id or matches.team2_teamid = teams.id  ) '
		. ' LEFT JOIN seasons ON ( matches.timestamp between seasons.startdate and seasons.enddate) '
		. ' WHERE seasons.id = ' . sqlSafeStringQuotes($seasonid));
		if (!($result = @$site->execute_query('seasons_results', $query, $connection)))
		{
			$site->dieAndEndPage('Could not recalculate seasons results due to a sql problem!');
		}
	
		//clear old season data
		$query = ('DELETE FROM `seasons_results` WHERE seasonid =' . sqlSafeStringQuotes($seasonid));
			if (!($resultdelete = @$site->execute_query('seasons_results', $query, $connection)))
		{
			$site->dieAndEndPage('Could not recalculate seasons results due to a sql problem!');
		}
		//go trough teams:	
		while ($row = mysql_fetch_array($result))
		{
			$teamid = $row['id'];
			$startdate = $row['startdate'];
			$enddate = $row['enddate'];
			$pointswin = $row['points_win'];
			$pointslost = $row['points_lost'];
			$pointsdraw = $row['points_draw'];
			//update teamwins, losts, and draws 
			$query = ('INSERT INTO  `seasons_results` (seasonid, teamid, wins, losts, draws) '
			. ' VALUES (' . sqlSafeStringQuotes($seasonid) . ',' . sqlSafeStringQuotes($teamid)
			. ' , ( ' //wins
				. ' SELECT COUNT(matches.id) FROM `teams` LEFT JOIN matches '
				. ' ON (`timestamp` between ' . sqlSafeStringQuotes($startdate) . ' AND ' . sqlSafeStringQuotes($enddate)
				. ' AND ((team1_teamid = ' . sqlSafeStringQuotes($teamid) . ' AND team1_points > team2_points )'
				. ' OR ( team2_teamid = ' . sqlSafeStringQuotes($teamid) . ' AND team2_points > team1_points )) )'
				. ' WHERE `teams`.id = ' . sqlSafeStringQuotes($teamid) 
			. ' ) , ( ' //losts
			. ' SELECT COUNT(matches.id) FROM `teams` LEFT JOIN matches'
				. ' ON (`timestamp` between ' . sqlSafeStringQuotes($startdate) . ' AND ' . sqlSafeStringQuotes($enddate)
				. ' AND ((team1_teamid = ' . sqlSafeStringQuotes($teamid) . ' AND team1_points < team2_points )'
				. ' OR ( team2_teamid = ' . sqlSafeStringQuotes($teamid) . ' AND team2_points < team1_points )) )'
				. ' WHERE `teams`.id = ' . sqlSafeStringQuotes($teamid) 
			. ' ) , ( ' //draws
			. ' SELECT COUNT(matches.id) FROM `teams` LEFT JOIN matches '
				. ' ON (`timestamp` between ' . sqlSafeStringQuotes($startdate) . ' AND ' . sqlSafeStringQuotes($enddate)
				. ' AND ( ( team1_teamid = ' . sqlSafeStringQuotes($teamid) . ' OR team2_teamid = ' . sqlSafeStringQuotes($teamid) . ' )'
				. ' AND team2_points = team1_points ) )'
				. ' WHERE `teams`.id = ' . sqlSafeStringQuotes($teamid) 
			. ' ) )');
			
			if (!($updateresult = $site->execute_query('seasons_results', $query, $connection)))
			{
				unlock_tables();
				$site->dieAndEndPage('The seasons results match calulations could not be updated due to a sql problem!');
			}
			unset($teamid);
			unset($startdate);
			unset($enddate);
			
		}
		
		mysql_free_result($result);
		//calculate teampoints
		
		$query = ('UPDATE `seasons_results` SET score = wins * ' . $pointswin . ' + losts * ' . $pointslost
		. ' + draws * ' . $pointsdraw . ', num_matches_played = wins + losts + draws'
		. ' WHERE seasonid = ' . sqlSafeStringQuotes($seasonid)		
		);
			
		if (!($result = $site->execute_query('seasons_results', $query, $connection)))
			{
				unlock_tables();
				$site->dieAndEndPage('The seasons results points could not be updated due to a sql problem!');
			}
			
		unset($pointswin);
		unset($pointslost);
		unset($pointsdraw);
		
		//getting best 3 teams
		$query = ('SELECT seasons.id, seasons_results.* FROM `seasons` ' 
		. ' LEFT JOIN seasons_results ON (seasons_results.seasonid = seasons.id) '
		. ' WHERE seasons.id = ' . sqlSafeStringQuotes($seasonid)		
		. ' ORDER BY seasons_results.score DESC, seasons_results.wins DESC, seasons_results.draws DESC LIMIT 0,3');
		
		if (!($result = @$site->execute_query('seasons, seasons_results', $query, $connection)))
		{
			unlock_tables();
			$site->dieAndEndPage('Could not get best teams due to a sql problem!');
		}
		//go trough teams:	
		$place_no=1;
		while ($row = mysql_fetch_array($result))
		{		
			$teamid =  $row['teamid'];
			$query = ('UPDATE `seasons` SET team_' . $place_no . ' = ' . $teamid 
			. ' WHERE id = ' . sqlSafeStringQuotes($seasonid));
				
			if (!($resultupdate = $site->execute_query('seasons', $query, $connection)))
				{
					unlock_tables();
					$site->dieAndEndPage('The seasons best teams could not be updated due to a sql problem!');
				}
			$place_no++;
		}
		unlock_tables();
	}
	
	function rankingLogo($score)
	{
		
		switch ($score)
		{
			case ($score >1900):
				echo '<span class="score s1900">';
				break;
			
			case ($score >1800):
				echo '<span class="score s1800">';
				break;
			
			case ($score >1700):
				echo '<span class="score s1700">';
				break;
			
			case ($score >1600):
				echo '<span class="score s1600">';
				break;
			
			case ($score >1500):
				echo '<span class="score s1500">';
				break;
			
			case ($score >1400):
				echo '<span class="score s1400">';
				break;
			
			case ($score >1300):
				echo '<span class="score s1300">';
				break;
			
			case ($score >1200):
				echo '<span class="score s1200">';
				break;
			
			case ($score >1100):
				echo '<span class="score s1100">';
				break;
			
			case ($score >1000):
				echo '<span class="score s1000">';
				break;
			
			case ($score >900):
				echo '<span class="score s900">';
				break;
			
			case ($score >800):
				
				echo '<span class="score s800">';
				break;
			
			case ($score >700):
				echo '<span class="score s700">';
				break;
			default :
				echo '<span class="score">';
		}
		
		echo $score;
		echo '</span>';
		
	}
	
?>