<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$allow_edit_season = false;
	if (isset($_SESSION['allow_edit_season']))
	{
		if (($_SESSION['allow_edit_season']) === true)
		{
			$allow_edit_season = true;
		}
	}
	

	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require ('../CMS/navi.inc');
	
	$connection = $site->connect_to_db();
	$viewerid = (int) getUserID();
	
	$confirmed = 1;
	$randomkey_name = 'seasons_rewards';
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

	if (!isset($_POST['reward']) && $_POST['reward'] != 'Submit rewards' && $_POST['reward'] != 'Confirm rewards'  ) {
		
		echo '<p>It looks like you came from somewhere else. Going back to compositing mode.</p>';
		$confirmed = 0;
		
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
		echo  $name ;
	}
	
	function player_name_from_id($id, $name)
	{
		echo $name;
	}
	
	
	echo '<div class="toolbar">';
	echo '</div>';
	
	$seasonid = 0;
	//get current season id if not set
	if (isset($_GET['season_id']))
	{
		$seasonid = intval($_GET['season_id']);
	} 
	if (isset($_GET['rewards']))
	{
		$seasonid = intval($_GET['rewards']);
	}
	
	$query = 'SELECT s.* FROM seasons s INNER JOIN seasons_results sr ON (s.id = sr.seasonid) ';
	// if there was an id in query try to get that season
	if ($seasonid != 0) 
	{
		$query .= ' WHERE s.id = ' . $seasonid;
	} //otherwise get last active.
	else $query .= ' WHERE active = true ORDER BY startdate DESC LIMIT 0,1 ';
	
	if (!($result = @$site->execute_query('seasons', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The last season could not be displayed because of an SQL/database connectivity problem.');
	}
	$rows = (int) mysql_num_rows($result);
	
	$allow_rewards = false;
	if ($rows === (int) 0)
	{
		echo '<p class="err_msg">No season to display.</p>' . "\n";
		setTableUnchanged($site, $connection);
		$site->dieAndEndPageNoBox();
	}
	else {
		$row = mysql_fetch_array($result);
		$seasonid = $row['id'];
		$startdate = $row['startdate'];
		$enddate = $row['enddate'];
		$isspecial = $row['is_special'];
		$seasonname = $row['name'];
		$allow_rewards = false;
		
		if ($allow_edit_season && $seasonid !=0 && isset($_GET['rewards']) && $row['is_rewarded'] == 0) 
		{
			$allow_rewards = true;
		}
	}
	unset($rows);
	unset($result);
	
	if ($_POST['reward'] == 'Submit rewards' )
	{
		// no permissions, just option to go seasons list
		if (!$confirmed || !$allow_rewards)   
		{ 
			echo '<div class="simple-paging">';
			echo '<a class="previous button" href="/Seasons">All seasons</a>' . "\n";
			echo '</div>';
		}
		// can add, display all the table with rewards to confirm
		else
		{
			
			echo '<h1 class="seasons">Adding rewards for season <span class="season-date">' . $startdate . ' - ' . $enddate . '</span> </h1>';
		
			$query = ('SELECT sr.*, p.name AS leader_name, t.name AS teamname, t.leader_playerid, tov.score AS rating '
			. ' FROM seasons_results sr '
			. ' LEFT JOIN teams t ON (t.id = sr.teamid) '
			. ' LEFT JOIN teams_overview tov ON (tov.teamid = sr.teamid) '
			. ' LEFT JOIN players p ON (p.id = t.leader_playerid) '
			. ' WHERE seasonid = ' . $seasonid
			. ' ORDER BY score DESC, wins DESC, draws DESC, losts DESC'	);
			
				
			if (!($result = @$site->execute_query('seasons_results', $query, $connection)))
			{
				$site->dieAndEndPageNoBox('The list of season results could not be displayed because of an SQL/database connectivity problem.');
			}
			
			$rows = (int) mysql_num_rows($result);
			
			if ($rows === (int) 0)
			{
				echo '<p class="message">No matches were played in this season.</p>' . "\n";
				setTableUnchanged($site, $connection);
				$site->dieAndEndPageNoBox();
			}
			unset($rows);
			
			
			echo '<div class="main-box">';
			if ($seasonname != '') 
			{
				echo '<h2>' . $seasonname . '</h2>';
			}
			echo '<form enctype="application/x-www-form-urlencoded" method="post" action="./update_season_rewards.php?rewards=' .$seasonid. '">'; 
			echo '<table id="table_seasons_results" class="big">' . "\n";
			echo '<tr>' . "\n";
			echo '	<th>Pos.</th>' . "\n";
			echo '	<th>Team</th>' . "\n";
			echo '	<th>Leader</th>' . "\n";
			echo '	<th>#</th>' . "\n";
			echo '	<th>Score</th>' . "\n";
			echo '	<th>W/L/T</th>' . "\n";
			echo '	<th>Rating</th>' . "\n";
			echo '	<th>Assigned rewards</th>' . "\n";
			
			echo '</tr>' . "\n\n";
			
			// display message overview
			$results_list = Array (Array ());
			// read each entry, row by row
			while ($row = mysql_fetch_array($result))
			{
				$id = (int) $row['id'];
				$results_list[$id]['teamid'] = $row['teamid'];
				$results_list[$id]['leader_playerid'] = $row['leader_playerid'];
				$results_list[$id]['rating'] = $row['rating'];
				$results_list[$id]['leader_name'] = $row['leader_name'];
				$results_list[$id]['teamname'] = $row['teamname'];		
				$results_list[$id]['score'] = $row['score'];
				$results_list[$id]['wins'] = $row['wins'];
				$results_list[$id]['losts'] = $row['losts'];
				$results_list[$id]['draws'] = $row['draws'];
				$results_list[$id]['num_matches_played'] = $row['num_matches_played'];		
				$results_list[$id]['id'] = $row['id'];		
	
				if (isset($_POST['team_'.$row['teamid']]))
				{
					$results_list[$id]['reward'] = intval($_POST['team_'.$row['teamid']]);
				}
				else $results_list[$id]['reward'] = 0;
			}
			unset($results_list[0]);
			
			// query result no longer needed
			mysql_free_result($result);
			
				
			// walk through the array values
			$lastscore = 0;
			$currentpos = 0;
			$matches_played = 0;
			$teams_played = 0;
			
			foreach($results_list as $result_entry)
			{
				echo '<tr class="seasons_results_overview">' . "\n";
				echo '<td>';
				if ($result_entry['score'] != $lastscore) 
				{
					echo ++$currentpos;
				}
				else echo $currentpos;
				echo '</td>' . "\n" . '<td>';
				team_name_from_id( $result_entry['teamid'],  $result_entry['teamname']);
				echo '</td>' . "\n";
				echo '<td>' . htmlent($result_entry['leader_name']) . '</td>' . "\n";
				echo '<td>' . $result_entry['num_matches_played'] . '</td>' . "\n";
				echo '<td>' . $result_entry['score'] . '</td>' . "\n";
				echo '<td>' . $result_entry['wins'] . '/' . $result_entry['losts'] . '/' .$result_entry['draws'] . ' </td>' . "\n";
				echo '<td>';
				rankingLogo($result_entry['rating']);
				echo '</td>' . "\n";
		
				if ($allow_rewards)
				{
					echo '	<td>' . "\n";
					$site->write_self_closing_tag('input type="text" readonly="readonly" title="" name="team_' . $result_entry['teamid'] .'" value="' . $result_entry['reward'] . '" class="small_input_field"');
					echo ' </td>' . "\n";
				}
				
				
				echo '</tr>' . "\n\n";
				$matches_played += $result_entry['num_matches_played'];
				$teams_played++;
			}
			unset($results_list);
			unset($result_entry);
			
			
			// no more matches to display
			echo '</table>' . "\n";
		
			generate_confirmation_key();
			$site->write_self_closing_tag('input type="submit" name="reward" value="Confirm rewards" id="send" class="button next"');
			echo '</form>'. "\n"; 
			
			echo '<table class="small">' . "\n";
			echo '<tr> <td> Number of matches in this season: </td> <td>' . ($matches_played/2) . '</td> </tr>';
			echo '<tr> <td> Number of active teams in this season: </td> <td>' . $teams_played . '</td> </tr>';
			echo '</table>' ."\n";
			
			unset($matches_played);
			unset($teams_played);
		}	
			
					
			
	}
	// after confirmation
	elseif ( $_POST['reward'] == 'Confirm rewards')
	{
		// no permissions, just go back to all seasons list
		if (!$confirmed || !$allow_rewards)
		{
			echo '<div class="simple-paging">';
			echo '<a class="previous button" href="/Seasons">All seasons</a>' . "\n";
			echo '</div>';
		}
		// display changes in ranks.
		else 
		{
			echo '<div class="main-box">' . "\n";
			echo '<p>Updating teams ranks:</p>' ."\n";
			foreach ($_POST as $key => $value)
			{
				if (strpos($key,'team_') == 0)
				{
					$teamid = intval(substr($key,5));
					$reward = intval($value);
	
					if ($reward != 0 && $teamid != 0) 
					{
						$query = ('SELECT t.name AS teamname, tov.score AS rating '
						. ' FROM teams t '
						. ' LEFT JOIN teams_overview tov ON (tov.teamid = t.id) ' 
						. ' WHERE t.id = ' . $teamid);
						
						
						
						if (!($result = @$site->execute_query('seasons_results_rewards_update', $query, $connection)))
						{
							$site->dieAndEndPageNoBox('The rewards for season results could not be submitted because of an SQL/database connectivity problem.');
						}
						if ($row = mysql_fetch_array($result))
						{
							$teamname = $row['teamname'];
							$oldrank = $row['rating'];
							$newrank = $oldrank + $reward;
							
							$query = ('UPDATE teams_overview SET score = ' . $newrank . ' WHERE teamid = ' .$teamid); 
							if (!($result = @$site->execute_query('seasons_team_results_rewards_update', $query, $connection)))
							{
								$site->dieAndEndPageNoBox('The rewards for team season results could not be submitted because of an SQL/database connectivity problem.');
							}											
							echo '<p>New rank for a team ' . $teamname . ' set to ' .$newrank .'. Old rank was ' . $oldrank . '</p>' . "\n";
							unset($row);
						}	
					}
				}
			}
			$query = ('UPDATE seasons SET is_rewarded = 1 WHERE id = ' . $seasonid);
												
			if (!($result = @$site->execute_query('seasons_results_rewards_confirm', $query, $connection)))
			{
				$site->dieAndEndPageNoBox('The rewards for season results could not be confirmed because of an SQL/database connectivity problem.');
			}
			echo '</div>';
		}		
	}
	
	if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		setTableUnchanged($site, $connection);
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
			
			default: echo '<span class="score">';
		}
		echo $score;
		echo '</span>';
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
	
	
?>
	</div>
</div>
</body>
</html>