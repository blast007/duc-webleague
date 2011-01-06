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
	
	$connection = $site->connect_to_db();
	$viewerid = (int) getUserID();
	
	
	
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
	
	
	echo '<div class="toolbar">';
	echo '</div>';
	
	$seasonid = 0;
	//get current season id if not set
	if (isset($_GET['season_id']))
	{
		$seasonid = intval($_GET['season_id']);
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
	}
	unset($rows);
	unset($result);

	
	
	echo '<h1 class="seasons">Results for season <span class="season-date">' . $startdate . ' - ' . $enddate . '</span> </h1>';
	
	echo '<div class="simple-paging">';
	
	// get next season
	$query = ('SELECT s.* FROM seasons s INNER JOIN seasons_results sr ON (s.id = sr.seasonid) '
	. ' WHERE active = true AND startdate > ' .  sqlSafeStringQuotes($startdate) 
	. '	ORDER BY startdate ASC LIMIT 0,1');

	if (($result = @$site->execute_query('seasons', $query, $connection))) {
		$rows = (int) mysql_num_rows($result);
		if ($rows != (int) 0)
		{
			$row = mysql_fetch_array($result);
			$nextseasonid = $row['id'];
			echo '<a class="button next " href="?season_id=' . $nextseasonid . '">Next season</a>';
		}
	}
	unset ($result);
	// get previous season
	$query = ('SELECT s.* FROM seasons s INNER JOIN seasons_results sr ON (s.id = sr.seasonid) '
	. ' WHERE active = true AND startdate < ' .  sqlSafeStringQuotes($startdate) 
	. '	ORDER BY startdate DESC LIMIT 0,1');

	if (($result = @$site->execute_query('seasons', $query, $connection))) {
		$rows = (int) mysql_num_rows($result);
		if ($rows != (int) 0)
		{
			$row = mysql_fetch_array($result);
			$previousseasonid = $row['id'];		
			echo '<a class="previous button" href="?season_id=' . $previousseasonid . '">Previous season</a>';
		}
	}
	echo '<a class="previous button" href="/Seasons">All seasons</a>' . "\n";
	
	echo '</div>';
	
	
	
	$query = ('SELECT sr.*, p.name AS leader_name, t.name AS teamname, t.leader_playerid, tov.score AS rating '
	. ' FROM seasons_results sr '
	. ' LEFT JOIN teams t ON (t.id = sr.teamid) '
	. ' LEFT JOIN teams_overview tov ON (tov.id = sr.teamid) '
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
	
	echo '<table id="table_seasons_results" class="big">' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Pos.</th>' . "\n";
	echo '	<th>Team</th>' . "\n";
	echo '	<th>Leader</th>' . "\n";
	echo '	<th>#</th>' . "\n";
	echo '	<th>Score</th>' . "\n";
	echo '	<th>W/L/T</th>' . "\n";
	echo '	<th>Rating</th>' . "\n";
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
		echo '<td><a href="'. basepath() . 'Players/?profile=' . $result_entry['leader_playerid'] . '">' . htmlent($result_entry['leader_name']) . '</a></td>' . "\n";
		echo '<td>' . $result_entry['num_matches_played'] . '</td>' . "\n";
		echo '<td>' . $result_entry['score'] . '</td>' . "\n";
		echo '<td>' . $result_entry['wins'] . '/' . $result_entry['losts'] . '/' .$result_entry['draws'] . ' </td>' . "\n";
		echo '<td>';
		rankingLogo($result_entry['rating']);
		echo '</td>' . "\n";

		
		echo '</tr>' . "\n\n";
		$matches_played += $result_entry['num_matches_played'];
		$teams_played++;
	}
	unset($results_list);
	unset($result_entry);
	
	
	// no more matches to display
	echo '</table>' . "\n";
	
	echo '<table class="small">' . "\n";
	echo '<tr> <td> Number of matches in this season: </td> <td>' . ($matches_played/2) . '</td> </tr>';
	echo '<tr> <td> Number of active teams in this season: </td> <td>' . $teams_played . '</td> </tr>';
	echo '</table>' ."\n";
	
	unset($matches_played);
	unset($teams_played);
	
	
			
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
	
	
	
?>
	</div>
</div>
</body>
</html>