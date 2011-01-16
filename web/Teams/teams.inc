<?php 

$sortBy = 'Name';
$sortByName = true; 
$order = 'asc';
$orderAsc = true;
$orderLink = 'desc';


function showTeamProfile($team_description, $team_leader_id, $allow_edit_any_team_profile, $display_user_kick_from_team_buttons )
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	
	$allow_invite_in_any_team = false;
	if (isset($_GET['profile']) || isset($_GET['edit']))
	{
		if (isset($_SESSION['allow_edit_any_team_profile']))
		{
			if (($_SESSION['allow_edit_any_team_profile']) === true)
			{
				$allow_edit_any_team_profile = true;
			}
		}
		if (isset($_SESSION['allow_invite_in_any_team']))
		{
			if (($_SESSION['allow_invite_in_any_team']) === true)
			{
				$allow_invite_in_any_team = true;
			}
		}
	}
	
	$can_delete_any_team = false;
	if (isset($_SESSION['allow_delete_any_team']) && ($_SESSION['allow_delete_any_team']))
	{
		$can_delete_any_team = true;
	}
		
	// display team profile page
				
	// clean variable
	$profile = (int) $_GET['profile'];
	echo '<div class="simple-paging"><a class="button previous" href="./">overview</a></div>' . "\n";
	
	echo '<div class="toolbar">';
	// message to team
	if (($team_leader_id > 0) && ($viewerid > 0))
	{
		echo '<a class="button" href="../Messages/?add' . htmlspecialchars('&') . 'teamid=' . htmlspecialchars(urlencode($profile)) . '">Send message to team</a>' . "\n";
	}
	
	// edit team
	if (($team_leader_id > 0) && $viewerid === $team_leader_id || $allow_edit_any_team_profile)
	{
		echo '<a class="button" href="./?edit=' . (urlencode($profile)) . '">edit</a>' . "\n";
	}
	
	// opponent stats
	echo '<a class="button" href="./?opponent_stats=' . urlencode($profile) . '">opponent stats</a>' . "\n";
	
	// activity stats
	echo '<a class="button" href="./?activity_stats=' . urlencode($profile) . '">activity stats</a>' . "\n";
	
	
	echo '</div>';
	
	// join the tables `teams`, `teams_overview` and `teams_profile` using the team's id
	$query = 'SELECT `name`, `score`, `activity`, `member_count`, `num_matches_played`, `num_matches_won`, `num_matches_draw`, `num_matches_lost`, `teams_profile`.`logo_url`, `created`';
	$query .= ' FROM `teams`, `teams_overview`, `teams_profile`';
	$query .= ' WHERE `teams`.`id` = `teams_overview`.`teamid` AND `teams_overview`.`teamid` = `teams_profile`.`teamid` AND `teams`.`id`=';
	$query .= sqlSafeStringQuotes($profile) . ' LIMIT 1';
	if (!($result = @$site->execute_query('teams, teams_overview, teams_profile', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	if ((int) mysql_num_rows($result) > 1)
	{
		// more than one team with the same id!
		// this should never happen
		$site->dieAndEndPage('There was more than one team with that id (' . sqlSafeString($profile) . '). This is a database error, please report it to admins.');
	}
	
	$team_name = '(no name)';
	$number_team_members = 0;
	
	while ($row = mysql_fetch_array($result))
	{
		echo '<div class="team_area main-box">' . "\n";
		echo '	<div class="team_header">' . "\n";
		$team_name = $row['name'];
		// team name empty?
		if (strcmp($team_name, '') === 0)
		{
			$team_name = '(unnamed team)';
		}
		echo '		<h2>' . $team_name . '</h2>' . "\n";
		if (!(strcmp(($row['logo_url']), '') === 0))
		{
			// user entered a logo
			$site->write_self_closing_tag('img class="team_logo" src="' . htmlent($row['logo_url']) . '" alt="team logo"');
		}
		echo '		<span class="team_score"><span class="label">Rating: </span>';
		rankingLogo($row['score']);
		echo '</span>' . "\n";
		echo '		<div class="team_activity"><span class="team_activity_announcement label">Activity: </span><span class="team_activity">'
			 . strval($row['activity']) . '</span></div>' . "\n";
		$number_team_members = (int) $row['member_count'];
		echo '		<div class="team_member_count"><span class="team_member_count_announcement label">Members: </span><span class="team_member_count">'
			 . strval($number_team_members) . '</span></div>' . "\n";
		echo '		<div class="team_created"><span class="team_created_announcement label">Created: </span><span class="team_created">'
			 . strval($row['created']) . '</span></div>' . "\n";
		
		// matches statistics: won, draw, lost and total
		echo '	<table id="table_team_matches_data" class="nested_table">' . "\n";
		echo '	<caption>Match statistics</caption>' . "\n";
		echo '	<tr>' . "\n";
		echo '		<th>Wins</th>' . "\n";
		echo '		<th>Draws</th>' . "\n";
		echo '		<th>Losses</th>' . "\n";
		echo '		<th>Total</th>' . "\n";
		echo '	</tr>' . "\n";
		
		echo '	<tr>' . "\n";
		echo '		<td>' . htmlentities($row['num_matches_won']) . '</td>' . "\n";
		echo '		<td>' . htmlentities($row['num_matches_draw']) . '</td>' . "\n";
		echo '		<td>' . htmlentities($row['num_matches_lost']) . '</td>' . "\n";
		echo '		<td><a href="../Matches/?search_string=';
		echo $team_name;
		echo '&search_type=team+name&amp;search_result_amount=200&search=Search">';
		echo htmlentities($row['num_matches_played']) . '</a></td>' . "\n";;
		echo '	</tr>' . "\n";
		echo '	</table>' . "\n";
		
		
		
		if ($row['num_matches_played'] > 0) {
			
			$won_ratio = number_format(($row['num_matches_won'] / $row['num_matches_played']) * 100,1);
			$tie_ratio = number_format(($row['num_matches_draw'] / $row['num_matches_played']) * 100,1);
			$loss_ratio = number_format(($row['num_matches_lost'] / $row['num_matches_played']) * 100,1);
		
				?>
	<div id="chart-summary"></div>
	<script src="/js/highcharts.js" type="text/javascript"></script>
	<script type="text/javascript" src="/js/themes/gray.js"></script>
	<script type="text/javascript">
	var chart1; // globally available
	$(document).ready(function() {
		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'chart-summary'
			},
			title: {
				text: ''
			},
			plotArea: {
				shadow: null,
				borderWidth: null,
				backgroundColor: null
			},
			tooltip: {
				formatter: function() {
					return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
				}
			},
			legend: {
				align: 'left',
				x: 10,
				verticalAlign: 'middle',
				y: 5,
				floating: false,
				backgroundColor: '#121212',
				borderColor: '#CCC',
				borderWidth: 1,
				width: 80,
				shadow: false
			},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: false
					},
					showInLegend: true
				}
			},

		    series: [{
				type: 'pie',
				name: 'Results ratio',
				data: [
					['Wins',   <?php echo  $won_ratio; ?>],
					['Losses',  <?php echo  $loss_ratio;?>],
					['Draws',    <?php echo  $tie_ratio;?>]
				]
			}]
		});
	});
	</script>
	<?php 
		
		}
		echo'	</div>' . "\n";
		echo '</div>' . "\n";
		
		echo '<div class="team_area main-box">' . "\n";
		echo '	<div class="team_profile_box">' . "\n";
		echo '		<div class="box-caption team_profile_header_text">Team description</div>' . "\n";
		echo '		<div class="team_description">' . $team_description . '</div>' . "\n";
		echo '	</div>' . "\n";
		echo '</div>' . "\n";
	
	}
	// query result no longer needed
	mysql_free_result($result);
	
	// no members -> team is dead
	if ($number_team_members === 0)
	{
		// FIXME: Show when (date) the team was deleted
		$site->dieAndEndPage('This team was deleted.');
	}
	
	
	// show the members of team!
	// only admins, team leader and members of the team in question can see last logins
	$query = ('SELECT `id` AS `is_member_of_team` FROM `players`'
		  . ' WHERE `players`.`id`=' . sqlSafeStringQuotes($viewerid)
		  . ' AND `players`.`teamid`=' . sqlSafeStringQuotes($profile)
		  . ' LIMIT 1');
	if (!($result = @$site->execute_query('players', $query, $connection)))
	{
		$site->dieAndEndPage('Could not find out if player #' . sqlSafeString($viewerid)
					. ' belongs to team #' . sqlSafeString($profile));
	}
	
	$team_member = false;
	while ($row = mysql_fetch_array($result))
	{
		if (intval($row['is_member_of_team']) > 0)
		{
			$team_member = true;
		}
	}
	mysql_free_result($result);
	
	// example query: SELECT `players`.`id`, `name`, `location`
	// FROM `players`, `players_profile` WHERE `players`.`teamid`='1' AND `players`.`id`=`players_profile`.`playerid`
	$query = ('SELECT `players`.`id`, `name`, `location`, `last_login`'
		  . ' FROM `players`, `players_profile` WHERE `players`.`teamid`=' . sqlSafeStringQuotes($profile)
		  . '  AND `players`.`id`=`players_profile`.`playerid`');
	if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	echo '<div class="main-box">';
	echo "\n" . '<table id="table_team_members" class="big">' . "\n";
	echo '<caption>Members of team ' . $team_name . '</caption>' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Name</th>' . "\n";
	echo '	<th>Location</th>' . "\n";
	echo '	<th>Permissions</th>' . "\n";
	if ($team_member || $display_user_kick_from_team_buttons)
	{
		echo '	<th>Last Login</th>' . "\n";
		echo '	<th>Allowed actions</th>' . "\n";
	}
	echo '</tr>' . "\n\n";

	while ($row = mysql_fetch_array($result))
	{
		echo '<tr class="teams_members_overview">' . "\n";
		echo '<td>';
		echo '<a href="../Players?profile=';
		$currentId = (int) $row['id'];
		echo $currentId . '">' . ($row['name']) . '</a>';
		echo '</td>' . "\n" . '<td>';
		$query = 'SELECT `name`, `flagfile` FROM `countries` WHERE `id`='. sqlSafeStringQuotes($row['location']);
		if (!($result_country = @$site->execute_query('teams_overview', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		$country_shown = false;
		while ($row_country = mysql_fetch_array($result_country))
		{
			$country_shown = true;
			if (!(strcmp($row_country['flagfile'], '') === 0))
			{
				$site->write_self_closing_tag('img alt="country flag" class="country_flag" src="../Flags/' . $row_country['flagfile'] . '"');
			}
			echo '<span class="user_profile_location">' . htmlent($row_country['name']) . '</span>' . "\n";
		}
		if (!$country_shown)
		{
			echo 'Could not find name for country with id ' . htmlent($row['location']);
		}
		mysql_free_result($result_country);
		unset($row_country);
		echo '</td>' . "\n" . '<td>';
		if (($team_leader_id > 0) && $team_leader_id === (int) $row['id'])
		{
			echo 'Leader';
		} else
		{
			// TODO: add team permissions into players database
			echo 'None';
		}
		echo '</td>' . "\n";
		
		if ($team_member || $display_user_kick_from_team_buttons)
		{
			echo '	<td>' . $row['last_login'] . '</td>' . "\n";
		}
		// team leader can't be removed from team
		if (($team_leader_id > 0) && ($currentId !== $team_leader_id))
		{
			if (($viewerid > 0) && ($viewerid === ((int) $row['id'])))
			{
				// user could leave his team
				echo '<td><a class="button" href="./?remove=' . $currentId . '">Leave team</a></td>' . "\n";
			} else
			{
				if ($display_user_kick_from_team_buttons)
				{
					// admin or team leader can kick a member from team
					echo '<td><a class="button" href="./?remove=' . $currentId . '">Kick member from team</a></td>' . "\n";						
				}
			}
		} else
		{
			// FIXME: Only display empty table cell when an action is possible, e.g. viewer is either admin or belongs to the team
			// nothing can be done -> display empty table cell
			
			// find out if viewer belongs to the team
			$user_belongs_to_the_viewed_team = false;
			$query = 'SELECT `teamid` FROM `players` WHERE `id`=' . sqlSafeStringQuotes($viewerid) . ' LIMIT 1';
			// debug output at this point would break the html definition thus execute the query silently
			if (!($team_origin_of_viewer = @$site->execute_silent_query('teams_overview', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			while($row = mysql_fetch_array($team_origin_of_viewer))
			{
				if (((int) $row['teamid']) === $profile)
				{
					$user_belongs_to_the_viewed_team = true;
				}
			}
			mysql_free_result($team_origin_of_viewer);
			
			if (($viewerid > 0) && ($user_belongs_to_the_viewed_team || (get_allow_kick_any_team_members() === true)))
			{
				echo '<td></td>';
			}
		}
		echo '</tr>' . "\n\n";
	}
	// query result no longer needed
	mysql_free_result($result);
	
	echo '</table>' . "\n";
	echo '</div>';
	
	echo '<div class="main-box">';
	
	// show last entered matches		
	// get match data
	// sort the data by id to find out if abusers entered data a loong time in the past
	$query = ('SELECT `timestamp`,`team1_teamid`,`team2_teamid`,'
			  . '(SELECT `name` FROM `teams` WHERE `id`=`team1_teamid`) AS `team1_name`'
			  . ',(SELECT `name` FROM `teams` WHERE `id`=`team2_teamid`) AS `team2_name`'
			  . ',`team1_points`,`team2_points`,`playerid`'
			  . ',(SELECT `players`.`name` FROM `players` WHERE `players`.`id`=`matches`.`playerid`) AS `playername`,`matches`.`id`'
			  . ' FROM `matches` WHERE `matches`.`team1_teamid`=' . sqlSafeStringQuotes($profile)
			  . ' OR `matches`.`team2_teamid`=' . sqlSafeStringQuotes($profile)
			  . ' ORDER BY `id` DESC LIMIT 0,10');
	if (!($result = @$site->execute_query('matches (subquery players)', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage();
	}
	
	
	if ((int) mysql_num_rows($result) === 0)
	{
		echo '<p>This team has played no matches yet.</p>';
	} else
	{
		// first find out permissions of currently viewing player
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
		
		// display the table
		echo '<table id="table_matches_played" class="big">' . "\n";
		echo '<caption>Last entered matches</caption>' . "\n";
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
		while ($row = mysql_fetch_array($result))
		{
			echo '<tr class="matches_overview">' . "\n";
			echo '<td>';
			echo $row['timestamp'];
			echo '</td>' . "\n" . '<td>';
			
			// get name of first team
			echo '<a href="../Teams/?profile=' . ((int) $row['team1_teamid']) . '">' . $row['team1_name'] . '</a>';
			
			// seperator showing that opponent team will be named soon
			echo ' - ';
			
			// get name of second team
			echo '<a href="../Teams/?profile=' . ((int) $row['team2_teamid']) . '">' . $row['team2_name'] . '</a>';
			
			// done with the table field, go to next field
			echo '</td>' . "\n" . '<td>';
			
			echo htmlentities($row['team1_points']);
			echo ' - ';
			echo htmlentities($row['team2_points']);
			echo '</td>' . "\n";
			
			echo '<td>';
			// get name of player that made last change
			echo '<a href="../Players/?profile=' . ((int) $row['playerid']) . '">' . $row['playername'] . '</a>';
			echo '</td>' . "\n";
			
			
			// show allowed actions based on permissions
			if ($allow_edit_match || $allow_delete_match)
			{
				echo '<td>';
				if ($allow_edit_match)
				{
					echo '<a class="button" href="../Matches/?edit=' . htmlspecialchars($row['id']) . '">Edit match result</a>';
				}
				if ($allow_edit_match && $allow_delete_match)
				{
					echo ' ';
				}
				if ($allow_delete_match)
				{
					echo '<a class="button" href="../Matches/?delete=' . htmlspecialchars(urlencode($row['id'])) . '">Delete match</a>';
				}
				echo '</td>' . "\n";
			}
			
			echo '</tr>' . "\n\n";
		}
		mysql_free_result($result);
		// no more matches to display
		echo '</table>' . "\n";
		echo '</div>';
	}
	
	// show pending invitations
	if (($team_leader_id > 0) && $viewerid === $team_leader_id || $allow_invite_in_any_team)
	{
		echo '<div class="main-box">';
		$query = 'SELECT `invited_playerid`, `expiration` FROM `invitations` WHERE `teamid`=' . sqlSafeStringQuotes($profile);
		$query .= ' ORDER BY `expiration`';
		if (!($result = @$site->execute_query('invitations', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('Could not get find out the player id and expiration date list of invited players');
		}
		
		$rows = (int) mysql_num_rows($result);
		if ($rows > 0)
		{
			echo "\n" . '<h3>Pending invitations:</h3>' . "\n";
			echo '<ul>' . "\n";
			
			while($row = mysql_fetch_array($result))
			{
				// get the id of invited player
				$playerid = (int) $row['invited_playerid'];
				$expirationDate = $row['expiration'];
				$query = 'SELECT `name` FROM `players` WHERE `id`=' . sqlSafeStringQuotes($playerid);
				if (!($result_name = @$site->execute_silent_query('players', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('Could not get find out the corresponding name of player id '
										 . sqlSafeString($playerid)
										 . ' from invited players');
				}
				// collect the data from query result array
				while($row = mysql_fetch_array($result_name))
				{
					$invited_player_name = $row['name'];
				}
				echo '<li>' . $invited_player_name . ' (expires ' . $expirationDate . ')</li>' . "\n";
			}
			echo '</ul>' . "\n";
		}
		echo '</div>';
	}
	
	// team leader can delete the team
	if ($can_delete_any_team || (($team_leader_id > 0) && ($viewerid === $team_leader_id)))
	{
		echo '<p class="toolbar"><a class="button" href="./?delete=' . (urlencode($profile)) . '">delete this team</a></p>' . "\n";
	}
	
	$site->dieAndEndPage();
}

function showTeamActivityStats($profile)
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	
	echo '<div class="simple-paging">';
	echo '<a class="button" href="./">overview</a>' . "\n";
	echo '<a class="button" href="./?profile=' . strval($profile) . '">back to team profile</a>' . "\n";
	echo '</div>';
	
	echo '<div class="static_page_box">' . "\n";
	
		$team_name = '(no name)';
		$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($profile) . ' LIMIT 0,1';
		if (!($result = @$site->execute_query('teams', $query, $connection)))
		{
			$site->dieAndEndPage('Could not find out name for team with id ' . sqlSafeString($profile));
		}
		while($row = mysql_fetch_array($result))
		{
			$team_name = $row['name'];
		}
		mysql_free_result($result);
	
		
		$query = 'SELECT MONTH(timestamp) as month, YEAR(timestamp) as year, count(id) as match_number'
		. ' FROM matches WHERE team1_teamid = ' . sqlSafeStringQuotes($profile) 
		. ' OR team2_teamid = ' . sqlSafeStringQuotes($profile) 
		. '	GROUP BY month, year ORDER BY year asc, month asc';
		
		if (!($result = @$site->execute_query('matches', $query, $connection)))
		{
			$site->dieAndEndPage('Could not find matches played.');
		}
		
		$n_rows = (int) mysql_num_rows($result);
		if ($n_rows < (int) 1)
		{
			mysql_free_result($result);
			echo '<p class="first_p">This team has not played a match yet.</p>';
			$site->dieAndEndPage();
		}
		$match_stats = array();
		$i = 0;
		$chart_periods = '';
		$chart_values = '';
		$total_match_num = 0;
		$start_month;
		$startyear = 2000;
		while ($row = mysql_fetch_array($result))
		{
			$match_stats[$i]['period'] = $row['month'] . '/' . $row['year'];
			$match_stats[$i]['match_number'] = $row['match_number'];
			$match_stats[$i]['month_number'] = (12 * ($row['year']-$startyear)) + $row['month']; 
			
			$total_match_num += $row['match_number'];
			
			if ($i != 0)
			{
				//if some team miss few months of activity
				if ($match_stats[$i]['month_number'] > ($match_stats[$i-1]['month_number'] + 1) )
				{
					for ($j=1; $j < ($match_stats[$i]['month_number'] - ($match_stats[$i-1]['month_number'])); $j++)
					{
						$chart_periods .= ",'" . getMonth($match_stats[$i-1]['month_number'] + $j, $startyear) . "'";
						$chart_values .= ",0";
					}
				} 
								
				$chart_periods .= ',';
				$chart_values .= ',';
			}
			$chart_periods .= "'" . $row['month'] . (($row['month'] === '6')? '<br/>' . $row['year']:'')  . "'";
			$chart_values .=  $match_stats[$i]['match_number'] ;
			
			$i++;
		}
		
		
		
		echo '<h2>Activity statistics for team ' . $team_name . '</h2>' . "\n";
		
		$months = $match_stats[$i-1]['month_number'] - $match_stats[0]['month_number'];
		echo '<table class="small" id="activity_summary">' . "\n";
		echo '<tr><td>Total match played: </td><td>' . $total_match_num . '</td></tr>' . "\n";
		echo '<tr><td>Average per month: </td><td>' . round($total_match_num / $months,2) . '</td></tr>' . "\n";
		echo '</table>';
		
		
		echo '<div id="chart-container-1"></div>' . "\n";
		
		echo '<table class="small team-activity" id="activity_stats">' . "\n";
		
		echo '<tr>' . "\n";
		echo '<th>Month</th>' . "\n";
		echo '<th>Played matches</th>' . "\n";
		echo '</tr>';
		
		
		for ($i = 0; $i < $n_rows; $i++)
		{
		if ($i>0 && (substr($match_stats[$i]['period'],-2) != substr($match_stats[$i-1]['period'],-2)))
			{
				echo '</table>' . "\n";
				echo '<table class="small team-activity" id="activity_stats">' . "\n";
				echo '<tr> <th>Month</th><th>Matches played</th></tr>' . "\n";
			}
			echo '<tr>';
			echo '<td>' . $match_stats[$i]['period'] . '</td>';
			echo '<td>' . $match_stats[$i]['match_number'] . '</td>';
			echo '</tr>' . "\n";			
		}
		
		echo '</table>' . "\n";
		
		
	echo '</div>';
	
	?>
	<script src="/js/highcharts.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/themes/gray.js"></script>
	<script type="text/javascript">
	var chart1; // globally available
	$(document).ready(function() {
	      chart1 = new Highcharts.Chart({
	         chart: {
	            renderTo: 'chart-container-1',
	            defaultSeriesType: 'line',
	         },
	         title: {
	            text: 'Activity stats for <?php echo addslashes(htmlent_decode($team_name)); ?>'
	         },
	         xAxis: {
					categories: [<?php echo $chart_periods; ?>]
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Amount of matches'
				}
			},
			legend: {
				enabled: false
			},
			tooltip: {
				formatter: function() {
					return '<b>'+ this.y + '</b>';
				}
			},
			plotOptions: {
				series: {
					stacking: 'normal'
				}
			},
		    series: [{ title: '',
				data: [<?php echo $chart_values; ?>]
			}]
	      });
	   });	
	</script>
	<?php 
	
	
	$site->dieAndEndPageNoBox();			
}

function showTeamOpponentsStats($profile)
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	global $sortBy;
	global $sortByName;
	global $order;
	global $orderAsc;
	global $orderLink;
	
	echo '<div class="simple-paging">';
	echo '<a class="button" href="./">overview</a>' . "\n";
	echo '<a class="button" href="./?profile=' . strval($profile) . '">back to team profile</a>' . "\n";
	echo '</div>';
	
	echo '<div class="static_page_box">' . "\n";
	
		$team_name = '(no name)';
		$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($profile) . ' LIMIT 0,1';
		if (!($result = @$site->execute_query('teams', $query, $connection)))
		{
			$site->dieAndEndPage('Could not find out name for team with id ' . sqlSafeString($profile));
		}
		while($row = mysql_fetch_array($result))
		{
			$team_name = $row['name'];
		}
		mysql_free_result($result);
		
		$query = 'SELECT * FROM `matches` WHERE `team1_teamid`=' . sqlSafeStringQuotes($profile) . '  OR `team2_teamid`=' . sqlSafeStringQuotes($profile);
		if (!($result = @$site->execute_query('teams', $query, $connection)))
		{
			$site->dieAndEndPage('Could not get team stats for team with id ' . sqlSafeString($profile));
		}
		
		$rows = (int) mysql_num_rows($result);
		if ($rows < (int) 1)
		{
			mysql_free_result($result);
			echo '<p class="first_p">This team has not played a match yet.</p>';
			$site->dieAndEndPage();
		}
		
		$match_stats = array();
		
		while ($row = mysql_fetch_array($result))
		{
			if (intval($row['team1_teamid']) === $profile)
			{
				// look up name if needed
				if (!(isset($match_stats[$row['team2_teamid']]['name'])))
				{
					$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($row['team2_teamid']) .' LIMIT 1';
					if (!($result_foreign_team = @$site->execute_query('teams', $query, $connection)))
					{
						echo '<div class="static_page_box">' . "\n";
						$site->dieAndEndPage('Could not get name for foreign team with id ' . sqlSafeString($profile));
					}
					while ($row_foreign_team = mysql_fetch_array($result_foreign_team))
					{
						$match_stats[$row['team2_teamid']]['name'] = $row_foreign_team['name'];
					}
					mysql_free_result($result_foreign_team);
				}
				
				if (intval($row['team1_points']) > intval($row['team2_points']))
				{
					// team 1 won
					if (isset($match_stats[$row['team2_teamid']]['won']))
					{
						$match_stats[$row['team2_teamid']]['won'] += 1;
					} else
					{
						$match_stats[$row['team2_teamid']]['won'] = 1;
					}
				} elseif (intval($row['team1_points']) < intval($row['team2_points']))
				{
					// team 1 lost
					if (isset($match_stats[$row['team2_teamid']]['lost']))
					{
						$match_stats[$row['team2_teamid']]['lost'] += 1;
					} else
					{
						$match_stats[$row['team2_teamid']]['lost'] = 1;
					}
				} else
				{
					// team 1 tied
					if (isset($match_stats[$row['team2_teamid']]['tied']))
					{
						$match_stats[$row['team2_teamid']]['tied'] += 1;
					} else
					{
						$match_stats[$row['team2_teamid']]['tied'] = 1;
					}
				}
			} else
			{
				// look up name if needed
				if (!(isset($match_stats[$row['team1_teamid']]['name'])))
				{
					$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($row['team1_teamid']) .' LIMIT 1';
					if (!($result_foreign_team = @$site->execute_query('teams', $query, $connection)))
					{
						echo '<div class="static_page_box">' . "\n";
						$site->dieAndEndPage('Could not get name for foreign team with id ' . sqlSafeString($profile));
					}
					while ($row_foreign_team = mysql_fetch_array($result_foreign_team))
					{
						$match_stats[$row['team1_teamid']]['name'] = $row_foreign_team['name'];
					}
					mysql_free_result($result_foreign_team);
				}
				
				if (intval($row['team1_points']) > intval($row['team2_points']))
				{
					// team 2 won
					if (isset($match_stats[$row['team1_teamid']]['lost']))
					{
						$match_stats[$row['team1_teamid']]['lost'] += 1;
					} else
					{
						$match_stats[$row['team1_teamid']]['lost'] = 1;
					}
				} elseif (intval($row['team1_points']) < intval($row['team2_points']))
				{
					// team 2 lost
					if (isset($match_stats[$row['team1_teamid']]['won']))
					{
						$match_stats[$row['team1_teamid']]['won'] += 1;
					} else
					{
						$match_stats[$row['team1_teamid']]['won'] = 1;
					}
				} else
				{
					// team 2 tied
					if (isset($match_stats[$row['team1_teamid']]['tied']))
					{
						$match_stats[$row['team1_teamid']]['tied'] += 1;
					} else
					{
						$match_stats[$row['team1_teamid']]['tied'] = 1;
					}
				}
			}
		}
		mysql_free_result($result);
		
		echo '<div id="chart-container-1"></div>' . "\n";
		
		echo '<table class="big" id="opponent_stats">' . "\n";
		echo '<caption>Opponent statistics for team ' . $team_name . '</caption>' . "\n";
		echo '<tr>' . "\n";
		
		// find out of table is to be sorted default - Name
		
		if (isset($_GET['sort']))
		{
			// allowed sorting columns
			$sort_colums = array('Name', 'Total', 'Total', 'Won', 'Tied', 'Lost', 'Won Ratio');
			
			$n_columns = count($sort_colums) -1;
			for ($i = 0; $i <= $n_columns; $i++)
			{
				if (strcmp($_GET['sort'], $sort_colums[$i]) === 0)
				{
					$sortBy = $_GET['sort'];
				}
			}
		}
		
		
		
		if (isset($_GET['order']) && strcmp($_GET['order'], 'desc') === 0)
		{
			$order = 'desc';
			$orderAsc = false;
			$orderLink = 'asc';
		}
			
		
		// write the navigation elements
		writeNavi('Name', $profile, $order, $orderLink);
		writeNavi('Total', $profile, $order, $orderLink);
		writeNavi('Won', $profile, $order, $orderLink);
		writeNavi('Tied', $profile, $order, $orderLink);
		writeNavi('Lost', $profile, $order, $orderLink);
		writeNavi('Won Ratio', $profile, $order, $orderLink);
		
		echo '</tr>' . "\n";
		

		
		// fill empty entries with 0 and compute won ratio
		$match_stats_keys = array_keys($match_stats);
		$n_teams = ((int) count($match_stats_keys)) - 1;
		for ($i = 0; $i <= $n_teams; $i++)
		{
			if (!isset($match_stats[$match_stats_keys[$i]]['won']))
			{
				$match_stats[$match_stats_keys[$i]]['won'] = 0;
			}
			if (!isset($match_stats[$match_stats_keys[$i]]['tied']))
			{
				$match_stats[$match_stats_keys[$i]]['tied'] = 0;
			}
			if (!isset($match_stats[$match_stats_keys[$i]]['lost']))
			{
				$match_stats[$match_stats_keys[$i]]['lost'] = 0;
			}
			
			$total = ($match_stats[$match_stats_keys[$i]]['won']
					  + $match_stats[$match_stats_keys[$i]]['tied']
					  + $match_stats[$match_stats_keys[$i]]['lost']);
			$match_stats[$match_stats_keys[$i]]['total'] = $total;
			$ratio = $match_stats[$match_stats_keys[$i]]['won'] / $total;
			$match_stats[$match_stats_keys[$i]]['won ratio'] = round($ratio*100, 2);
		}
		
		// sort the array
		if (!(strcmp($sortBy, '') === 0))
		{
			// mark whether to sort by name for callback
			$sortByName = (strcmp($sortBy, 'Name') === 0);
			uasort($match_stats, 'cmp');
			//unset($sortByName);
			
			// re-index the key lookup table
			// NOTE: would also be necessary if uasort would be used to sort the array 
			$match_stats_keys = array_keys($match_stats);
		}
		
		$chart_teams="";
		$chart_wins="";
		$chart_losses="";
		$chart_ties="";
		
		for ($i = 0; $i <= $n_teams; $i++)
		{
			echo '<tr>';
			
			echo '<td>';
			if (isset($match_stats[$match_stats_keys[$i]]['name']))
			{
				echo '<a href=".?profile=' . htmlent($match_stats_keys[$i]) . '">' . $match_stats[$match_stats_keys[$i]]['name'] . '</a>';
			}
			echo '</td>';
			
			echo '<td>' . $match_stats[$match_stats_keys[$i]]['total'] . '</td>';
			
			echo '<td>' . $match_stats[$match_stats_keys[$i]]['won'] . '</td>';
			
			echo '<td>' . $match_stats[$match_stats_keys[$i]]['tied'] . '</td>';
			
			echo '<td>' . $match_stats[$match_stats_keys[$i]]['lost'] . '</td>';
			
			echo '<td>' . number_format($match_stats[$match_stats_keys[$i]]['won ratio'], 2) . ' %</td>';
			
			echo '</tr>' . "\n";
		
			//counting for charts
			if ($i > 0) 
			{
				$chart_teams .= ','; 
				$chart_wins .= ',';
				$chart_losses .= ',';
				$chart_ties .= ','; 
			}
			$chart_teams .= '\'' . addslashes(htmlent_decode($match_stats[$match_stats_keys[$i]]['name'])) . '\'';
			$chart_wins .= $match_stats[$match_stats_keys[$i]]['won'];
			$chart_losses .= $match_stats[$match_stats_keys[$i]]['lost'];
			$chart_ties .= $match_stats[$match_stats_keys[$i]]['tied'];
			
		}
		
		echo '</table>' . "\n";
	echo '</div>';
		
	?>
	<script src="/js/highcharts.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/themes/gray.js"></script>
	<script type="text/javascript">
	var chart1; // globally available
	$(document).ready(function() {
	      chart1 = new Highcharts.Chart({
	         chart: {
	            renderTo: 'chart-container-1',
	            defaultSeriesType: 'bar',
	            height: <?php echo (($n_teams * 20) + 150);?>
	         },
	         title: {
	            text: 'Opponents summary for <?php echo addslashes(htmlent_decode($team_name)); ?>'
	         },
	         xAxis: {
					categories: [<?php echo $chart_teams; ?>]
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Amount of matches'
				}
			},
			legend: {
				align: 'right',
				x: -100,
				verticalAlign: 'top',
				y: 10,
				floating: true,
				backgroundColor: '#121212',
				borderColor: '#CCC',
				borderWidth: 1,
				shadow: false
			},
			tooltip: {
				formatter: function() {
					return '<b>' + '<?php echo addslashes(htmlent_decode($team_name)); ?>' + ' vs ' + this.x +'</b><br/>'+
						 this.series.name +': '+ this.y +'<br/>'+
						 'Total: '+ this.point.stackTotal;
				}
			},
			plotOptions: {
				series: {
					stacking: 'normal'
				}
			},
		    series: [{
				name: 'Wins',
				data: [<?php echo $chart_wins; ?>]
			}, {
				name: 'Losses',
				data: [<?php echo $chart_losses; ?>]
			}, {
				name: 'Ties',
				data: [<?php echo $chart_ties; ?>]
			}]
	      });
	   });	
	</script>
	<?php 
	
	
	$site->dieAndEndPageNoBox();
}

function removePlayerFromTeam($playerid, $teamid, $team_leader_id, $display_user_kick_from_team_buttons)
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	
	echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
	echo '<div class="static_page_box">' . "\n";
	
	$playerid_to_remove = (int) $_GET['remove'];
	
	if ($viewerid < 1)
	{
		echo '<p>You must login to remove a player from team.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	// team captain can not kick himself
	if ($playerid === $team_leader_id)
	{
		echo '<p>You may not kick yourself from the team because you are the team leader.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	// team captain can only kick his own team members
	if (!get_allow_kick_any_team_members())
	{
		$query = ('SELECT `teamid` FROM `players`'
				  . ' WHERE `teamid`=(SELECT `teamid` FROM `players` WHERE `id`=' . sqlSafeStringQuotes($viewerid) . ')'
				  . ' AND `id`=' . sqlSafeStringQuotes($playerid));
		if (!($result = @$site->execute_query('teams', $query, $connection)))
		{
			$site->dieAndEndPage('Could not find out who belongs to same team of player with id ' . $viewerid);
		}
		$player_to_kick_in_same_team = false;
		while($row = mysql_fetch_array($result))
		{
			$player_to_kick_in_same_team = true;
		}
		
		if (!$player_to_kick_in_same_team)
		{
			echo '<p>You can only kick your own teammates from the team.</p>' . "\n";
			$site->dieAndEndPage('');
		}
	}
	
	// has the kick request been confirmed?
	$confirmed = 0;
	if (isset($_POST['confirmed']))
	{
		$confirmed = (int) $_POST['confirmed'];
	}
	
	// if the request looks valid investigate further
	if ($confirmed === 1)
	{
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);			
		if (!($randomkeysmatch))
		{
			echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
			$site->dieAndEndPage('');
		}
		
		if ($display_user_kick_from_team_buttons || (($viewerid > 0) && ($viewerid === $playerid_to_remove)))
		{
			// user is allowed to kick members from team
			$query = 'UPDATE `players` SET `teamid`=' . sqlSafeStringQuotes('0');
			$query .= ' WHERE `id`=' . sqlSafeStringQuotes(intval($playerid));
			if (!($result = @$site->execute_query('players', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('There was a problem preventing the user with id ('
									 . sqlSafeString($viewerid)
									 . ') from being kicked from team with id ('
									 . htmlentities($join_team_id)) . ')';
			}
			
			// update member count of team
			$query = 'UPDATE `teams_overview` SET `member_count`=(SELECT COUNT(*) FROM `players` WHERE `players`.`teamid`=';
			$query .= "'" . sqlSafeString($teamid) . "'" . ') WHERE `teamid`=';
			$query .= "'" . sqlSafeString($teamid) . "'";
			if (!($result = @$site->execute_query('teams_overview, nested players ', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('There was a problem updating the member count of team id ('
									 . sqlSafeString($viewerid) . ') from being kicked from team with id ('
									 . htmlentities($join_team_id)) . ')';
			}
			
			if ($playerid_to_remove === $viewerid)
			{
				echo '<p>You successfully left the team!</p>';
			} else
			{
				echo '<p>The user was successfully kicked from the team!</p>';
			}
		} else
		{
			// someone tried to kick a member without permissions
			$site->dieAndEndPage('You (' . htmlentities(sqlSafeString($viewerid))
								 . ') are not allowed to kick that member ('
								 . htmlentities($playerid_to_remove) . ') from his team');
		}
		$site->dieAndEndPage('');
	}
	
	
	
	$query = 'SELECT `name` FROM `players` WHERE `id`=' . "'" . sqlSafeString($playerid_to_remove) . "'";
	if (!($result = @$site->execute_query('players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	// take care of potential database problems
	checkNumberRows($result, $site);
	
	// get the name of the player being kicked from the query result
	$playername = '(none)';
	while($row = mysql_fetch_array($result))
	{
		$playername = $row['name'];
	}
	mysql_free_result($result);
	
	// display a form, asking for confirmation of kick
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?remove=' . urlencode($playerid_to_remove) . '">' . "\n";
	echo '<div><input type="hidden" name="confirmed" value="1"></div>' . "\n";

	$new_randomkey_name = $randomkey_name . microtime();
	$new_randomkey = $site->set_key($new_randomkey_name);
	echo '<div><input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"></div>' . "\n";
	echo '<div><input type="hidden" name="' . htmlentities($randomkey_name) . '" value="';
	echo urlencode(($_SESSION[$new_randomkey_name])) . '"></div>' . "\n";
	
	// let it say leave the team if a member of the team wants to leave it
	if ($playerid_to_remove === $viewerid)
	{
		echo '<p style="display:inline">Do you really want to leave your team?</p>' . "\n";
		echo '<div style="display:inline"><input type="submit" name="kick_user_from_team" value="Leave the team" id="send"></div>' . "\n";
	} else
	{
		echo '<p style="display:inline">Do you really want to kick ' . $playername . ' from the team?</p>' . "\n";
		echo '<div style="display:inline"><input type="submit" name="kick_user_from_team" value="Kick the user" id="send"></div>' . "\n";
	}
	echo '</form>' . "\n";
	$site->dieAndEndPage('');
}

function editTeam($team_leader_id)
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	global $logo_url;
	
	if ($viewerid < 1)
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		
		echo '<p>You must login to edit the team page.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	if (isset($_GET['profile']) || isset($_GET['edit']))
	{
		if (isset($_SESSION['allow_edit_any_team_profile']))
		{
			if (($_SESSION['allow_edit_any_team_profile']) === true)
			{
				$allow_edit_any_team_profile = true;
			}
		}
	}
	
	$teamid = (int) $_GET['edit'];
	if ($teamid < 1)
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') did not specify a team to edit.');
	}
	
	if (!(($viewerid === $team_leader_id) || $allow_edit_any_team_profile))
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') have no permissions to edit the team profile (id=' . sqlSafeString($teamid) . ').');
	}		
	
	$team_name = '(no name)';
	$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($teamid) . ' LIMIT 0,1';
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// check if team exist
	$rows = (int) mysql_num_rows($result);
	if ($rows < (int) 1)
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') tried to edit a nonexisting team (id=' . sqlSafeString($teamid) . ').');
	}
	
	// only one team must exist for each id
	// if more than one team exists for a given id then it is no user error but a database problem
	if ($rows > (int) 1)
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		$site->dieAndEndPage('There is more than one team with the id '
							 . sqlSafeString($teamid)
							 . ' in the database! This is a database problem, please report it to admins!');
	}
	
	while($row = mysql_fetch_array($result))
	{
		$team_name = $row['name'];
	}
	mysql_free_result($result);
	
	if (isset($_POST['confirmed']) && (isset($_POST['edit_team_name']) || isset($_POST['team_description'])))
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		
		// someone is trying to break the form
		// TODO: implement preview
		if (($_POST['confirmed'] < 1) || ($_POST['confirmed'] > 2))
		{
			$site->dieAndEndPage('Your (id='. sqlSafeString($viewerid) . ') attempt to insert wrong data into the form was detected.');
		}
					
		// general permissions given, do further sanity checks
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
		
		if (!($randomkeysmatch))
		{
			echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
			$site->dieAndEndPage('');
		}
		
		// update the team name if new team name is not equal to "(teamless)"
		if (isset($_POST['edit_team_name']))
		{
			// is the team name already used?
			$query = 'SELECT `id` FROM `teams` WHERE `name`=' . sqlSafeStringQuotes(htmlent($_POST['edit_team_name'])) . ' LIMIT 1';
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				$site->dieAndEndPage('Could not find out id for team with name ' . sqlSafeString(htmlent($_POST['edit_team_name'])) . '.');
			}
			
			if ((int) mysql_num_rows($result) > 0)
			{
				// is the team owning the name the current team?
				$name_change_tried = true;
				while ($row = mysql_fetch_array($result))
				{
					if (((int) $row['id']) === ((int) $teamid))
					{
						// there was no name changed tried
						$name_change_tried = false;
					}
				}
				mysql_free_result($result);
				// suppress error message when no name change was tried
				if ($name_change_tried)
				{
					// team name already used -> do not change to team name to it
					// note: this also happens when the team profile is changed but not its name
					echo '<p>The team name was not changed because there is already a team with that name in the database.</p>' . "\n";
				}
			} else
			{
				mysql_free_result($result);
				// team name not used -> set team name to it
				$name = cleanTeamName($_POST['edit_team_name']);
				if ($name === false)
				{
					// team name not clean -> do not change to team name to it
					echo ('<p>The team name was not changed because there are issues with it.'
						  . ' A team name must not end with whitespace and must not contain non-printable characters'
						  . ' as well as being not equal to the reserved name "(teamless)".</p>' . "\n");
				} else
				{
					$query = 'UPDATE `teams` SET `name`=' . sqlSafeStringQuotes($name);
					$query .= ' WHERE `id`=' . "'" . $teamid . "'";
					if (!($result = @$site->execute_query('teams', $query, $connection)))
					{
						$site->dieAndEndPage('Could not update name for team with id ' . sqlSafeString($teamid) . '.');
					}
				}
			}
		}
		
		//update team leader
		if (isset($_POST['team_leader']))
		{
			$new_leader = (int) $_POST['team_leader'];
			if ($new_leader  < 1)
			{
				$site->dieAndEndPage('You (id='
									 . sqlSafeString($viewerid)
									 . ') tried to set a new leader with id lower than 1 ('
									 . sqlSafeString($new_leader)
									 . ')!');
			}
			
			// find out if new leader is member of team
			// first get the list of playerid's belonging to that team
			$query = 'SELECT `id` FROM `players` WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
			if (!($result = @$site->execute_query('players', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			
			$new_leader_belongs_to_same_team = false;
			while($row = mysql_fetch_array($result))
			{
				if ($new_leader === ((int) $row['id']))
				{
					$new_leader_belongs_to_same_team = true;
				}
			}
			mysql_free_result($result);
			
			if (!$new_leader_belongs_to_same_team)
			{
				// verbose output, because someone tried to enter wrong data on purpose with a manipulated form
				$output = 'You (id=' . sqlSafeString($viewerid) . ') tried to set a new leader (id= ' . sqlSafeString($new_leader);
				$output .= ') that does not belong to same team (id=' . sqlSafeString($teamid) . ')!';
				$site->dieAndEndPage($output);
			}
			
			// sanity checks passed, change the team leader
			$query = 'UPDATE `teams` SET `leader_playerid`=' . sqlSafeStringQuotes($new_leader) . ' WHERE `id`=' . "'" . sqlSafeString($teamid) . "'";
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
		}
		
		// can any teamless player can join the team?
		if (isset($_POST['any_teamless_player_can_join']))
		{
			// value will only be sent when checkbox is checked and even in that case it only has the following value
			if ((int) $_POST['any_teamless_player_can_join'] === 1)
			{
				// team is open
				// hardcode the value to prevent sql injections
				$query = 'UPDATE `teams_overview` SET `any_teamless_player_can_join`=' . "'" . '1' . "'" . ' WHERE `teamid`=' . "'" . sqlSafeString($teamid) . "'";
				if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
				{
					// query was bad, error message was already given in $site->execute_query(...)
					$site->dieAndEndPage('');
				}
			}
		} else
		{
			// the team was closed
			// hardcode the value to prevent sql injections
			$query = 'UPDATE `teams_overview` SET `any_teamless_player_can_join`=' . "'" . '0' . "'" . ' WHERE `teamid`=' . "'" . sqlSafeString($teamid) . "'";
			if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
		}
		
		// update team description
		if (isset($_POST['team_description']))
		{
			$query = 'UPDATE `teams_profile` SET `description`=' . sqlSafeStringQuotes($site->bbcode($_POST['team_description']));
			$query .= ',`raw_description`=' . sqlSafeStringQuotes($_POST['team_description']);
			$query .= ' WHERE `teamid`=' . "'" . $teamid . "'";
			if (!($result = @$site->execute_query('teams_profile', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
		}
		
		// write logo
		writeLogo();
		
		echo '<p>Changes were successfully written.</p>';
		
		// we're done editing
		$site->dieAndEndPage('');
	}
			
	echo '<div class="simple-paging"><a class="button" href="./">Cancel &amp; back to overview</a></div>' . "\n";
	echo '<div class="static_page_box">' . "\n";
	
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?edit=' . urlencode($teamid) . '">' . "\n";
	echo '<div><input type="hidden" name="confirmed" value="1"></div>' . "\n";
	
	$new_randomkey_name = $randomkey_name . microtime();
	$new_randomkey = $site->set_key($new_randomkey_name);
	echo '<div><input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"></div>' . "\n";
	echo '<div><input type="hidden" name="' . htmlentities($randomkey_name) . '" value="';
	echo urlencode(($_SESSION[$new_randomkey_name])) . '"></div>' . "\n";
	
	// team name
	echo '<p><label class="team_change" for="edit_team_name">Change team name: </label>' . "\n";
	echo '<input type="text" maxlength="30" size="30" name="edit_team_name" value="' . ($team_name) . '" id="edit_team_name"></p>' . "\n";
	
	// team leader
	$query = 'SELECT `id`, `name` FROM `players` WHERE `teamid`=' . "'" . sqlSafeString($teamid) . "'";
	if (!($result = @$site->execute_query('players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	echo '<p><label class="team_change" for="team_leader">Leader:</label>' . "\n";
	echo '<select name="team_leader" id="team_leader">' . "\n";
	while($row = mysql_fetch_array($result))
	{
		echo '	<option';
		if ((int) $row['id'] === $team_leader_id)
		{
			echo ' selected="selected"';
		}
		echo ' value="' . urlencode($row['id']) . '"';
		echo '>' . ($row['name']);
		echo '</option>' . "\n";
	}
	mysql_free_result($result);
	echo '</select>' . "\n";
	echo '</p>' . "\n";
	
	// any teamless player allowed to join?
	$query = 'SELECT `any_teamless_player_can_join` FROM `teams_overview` WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
	if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	echo '<p><label class="team_change" for="any_teamless_player_can_join">Can any teamless player join the team?</label> <input type="checkbox" name="any_teamless_player_can_join" value="';
	echo '1' . '" id="any_teamless_player_can_join"';
	
	while($row = mysql_fetch_array($result))
	{
		if ((int) $row['any_teamless_player_can_join'] === 1)
		{
			echo ' checked="checked"';
		}
	}
	mysql_free_result($result);
	echo '></p>';
	
	// team description
	// team description
	if ($site->bbcode_lib_available())
	{
		echo "\n" . '<div class="team_change" id="bbcode_buttons1" style="display: inline-block;">';
		echo '<div class="invisi" style="display: inline;">' . "\n";
		echo '	<label class="team_change">bbcode:</label></div>' . "\n";
		echo '<span class="bbcode_buttons">';
		echo '<span class="bbcode_buttons">';
		include dirname(dirname(__FILE__)) . '/CMS/bbcode_buttons.php';
		$bbcode = new bbcode_buttons();
		// set up name of field to edit so javascript knows which element to manipulate
			$bbcode->showBBCodeButtons('team_description');
			unset($bbcode);
			echo '</span>';
			echo "\n";
			echo '</div>' . "\n";
		}
		echo '<div><label class="team_change" for="team_description">Edit team description: </label><span><textarea id="team_description" rows="10" cols="50" name="team_description">';
		if (isset($team_description))
		{
			echo $team_description;
		} else
		{
			echo 'Think of a good description';
		}
		echo '</textarea></span></div>' . "\n";
		
		// logo/avatar url
		echo '<p><label class="team_change" class="player_edit" for="edit_avatar_url">Avatar URL: </label>';
		$site->write_self_closing_tag('input id="edit_avatar_url" type="text" name="logo_url" maxlength="200" size="60" value="'. $logo_url .'"');
		echo '</p>';
		
		// close static box
		echo '</div>';
		
		echo '<p><input type="submit" name="edit_team_data" value="Submit new team data" id="send" class="button"></p>' . "\n";
		echo '</form>' . "\n";
		$site->dieAndEndPageNoBox();
}


function deleteTeam($teamid)
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	global $team_leader_id;
	global $can_delete_any_team;
	
	
	// no anon team deletion
	if ($viewerid < 1)
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<p>You must login to delete the team.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	if (!($team_leader_id === $viewerid) && !$can_delete_any_team)
	{
		$site->dieAndEndPage('You (id='
							 . sqlSafeString($viewerid)
							 . ') attempted to delete a team (id='
							 . sqlSafeString($teamid)
							 . ') without having permission to do that.');
	}
	
	if (isset($_POST['confirmed']))
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		if (($_POST['confirmed'] < 1) || ($_POST['confirmed'] > 2))
		{
			$site->dieAndEndPage('Your (id='. sqlSafeString($viewerid) . ') attempt to delete a team by manipulating the form was detected.');
		}
		
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
		
		if (!($randomkeysmatch))
		{
			echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
			$site->dieAndEndPage('');
		}
		
		// do the team deletion
		// the team is only marked as deleted because team history is kept
		$query = 'SELECT `deleted` FROM `teams_overview`';
		$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
		$query .= ' LIMIT 1';
		if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		while($row = mysql_fetch_array($result))
		{
			if (((int) $row['deleted'] === 1) || ((int) $row['deleted'] === 2) || ((int) $row['deleted'] === 3))
			{
				// mark team as deleted (2)
				$query = ('UPDATE `teams`,`teams_overview`'
						  . ' SET `teams_overview`.`deleted`=' . sqlSafeStringQuotes('2')
						  . ' ,`teams_overview`.`member_count`=' . sqlSafeStringQuotes('0')
                                                          . ',`teams`.`leader_playerid`=' . sqlSafeStringQuotes('0')
						  . ' WHERE `teams_overview`.`teamid`=' . sqlSafeStringQuotes($teamid)
						  . ' AND `teams`.`id`=`teams_overview`.`teamid`');
				if (!($result_update = @$site->execute_query('teams_overview', $query, $connection)))
				{
					$site->dieAndEndPage('Could not mark deleted team #' . sqlSafeStringQuotes($teamid) . ' as active.');
				}
				
			}
			
			if ((int) $row['deleted'] === 0)
			{
				// delete (for real) the new team
				$query = 'DELETE FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($teamid);
				// execute query, ignore result
				@$site->execute_query('teams', $query, $connection);
				$query = 'DELETE FROM `teams_overview` WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
				// execute query, ignore result
				@$site->execute_query('teams_overview', $query, $connection);
				$query = 'DELETE FROM `teams_permissions` WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
				// execute query, ignore result
				@$site->execute_query('teams_permissions', $query, $connection);
				$query = 'DELETE FROM `teams_profile` WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
				// execute query, ignore result
				@$site->execute_query('teams_profile', $query, $connection);	
			}
		}
		
		// mark who was where, to easily restore an unwanted team deletion
		$query = 'UPDATE `players` SET `last_teamid`=' . sqlSafeStringQuotes($teamid);
		$query .= ', `teamid`=' . sqlSafeStringQuotes('0');
		$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($teamid);
		if (!($result_update = @$site->execute_query('players', $query, $connection)))
		{
			$site->dieAndEndPage('Could not set previous team information for team #' . sqlSafeStringQuotes($teamid) . '.');
		}
		
		// always remember who deleted what
		
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') successfully deleted the team (id=' . sqlSafeString($teamid) . ').');
		echo '</div>';
	}
	// display a form, asking for confirmation of team deletion
	echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
	echo '<div class="static_page_box">' . "\n";
	
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?delete=' . urlencode($teamid) . '">' . "\n";
	echo '<div><input type="hidden" name="confirmed" value="1"></div>' . "\n";
	
	$new_randomkey_name = $randomkey_name . microtime();
	$new_randomkey = $site->set_key($new_randomkey_name);
	echo '<div><input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"></div>' . "\n";
	echo '<div><input type="hidden" name="' . htmlentities($randomkey_name) . '" value="';
	echo urlencode(($_SESSION[$new_randomkey_name])) . '"></div>' . "\n";
	echo 'Do you really want to delete the team?' . "\n";
	echo '<input type="submit" name="delete_team" value="Delete the team" id="send" class="button">' . "\n";
	echo '</form>' . "\n";
	$site->dieAndEndPage('');
}

function joinTeam()
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	
	$join_team_id = (int) $_GET['join'];
		
	// silently ignore if someone tries to join nonexisting team
	if ($join_team_id === 0)
	{
		$site->dieAndEndPage('');
	}
	
	// find out if player is already member of a team
	$query = 'SELECT `teamid` FROM `players` WHERE `id`=' . "'" . sqlSafeString($viewerid) . "'" . ' LIMIT 0,1';
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	checkNumberRows($result, $site);
	
	// basically following a simple link could let a teamless player join a team
	// FIXME: maybe use a form for joining to prevent the latter
	while($row = mysql_fetch_array($result))
	{
		if (!((int) $row['teamid'] === 0))
		{
			echo '<p class="main-box">You are already member of a team and thus you can not join another team.</p>';
			$site->dieAndEndPage('');
		}
	}
	mysql_free_result($result);
	
	// FIXME: collects way too much info?
	$query = 'SELECT `teams`.`id`,  `teams`.`name`, `teams_overview`.`any_teamless_player_can_join` FROM `teams`, `teams_overview`';
	$query .= ' WHERE `teams`.`id`=' . "'" . sqlSafeString($join_team_id) . "'";
	$query .= ' AND `teams`.`id`=`teams_overview`.`teamid`';
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	checkNumberRows($result, $site);
	
	$team_name = '(no name)';
	if (mysql_num_rows($result) === 1)
	{
		$allowed_to_join = 0;
		$invite_available = false;
		while($row = mysql_fetch_array($result))
		{
			$allowed_to_join = (int) $row['any_teamless_player_can_join'];
			$team_name = $row['name'];
		}
		mysql_free_result($result);
		
		// team not open for all players
		if (!($allowed_to_join === 1))
		{
			// check for invite!
			$query = 'SELECT `invited_playerid`, `expiration` FROM `invitations` WHERE `teamid`=' . "'" . sqlSafeString($join_team_id) . "'";
			$query .= ' AND `invited_playerid`=' . sqlSafeStringQuotes($viewerid) . ' ORDER BY `expiration`';
			if (!($result = @$site->execute_query('invitations', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('Could not get find out the player id and expiration date list of invited players');
			}
			while($row = mysql_fetch_array($result))
			{
				$allowed_to_join = (int) 1;
				$invite_available = true;
			}
			mysql_free_result($result);
			
			if (!($allowed_to_join === 1))
			{
				echo '<p>You have no permission to join that team</p>';
				$site->dieAndEndPage('');
			}
		}
	} else
	{
		$site->dieAndEndPage('There was more than one team with the same id ('. sqlSafeString($join_team_id) . ' or the team did not exist. This should never happen.');
	}
	
	if (isset($_POST['confirmed']) && ((int) $_POST['confirmed'] === 1))
	{
		echo '<div class="simple-paging"><a class="button" href="./">overview</a></div>' . "\n";
		echo '<div class="static_page_box">' . "\n";
		
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);						
		if (!($randomkeysmatch))
		{
			echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
			$site->dieAndEndPage('');
		}
		
		if ($allowed_to_join === 1)
		{
			$query = 'UPDATE `players` SET `teamid`=' . "'" . sqlSafeString($join_team_id) . "'";
			$query .= ' WHERE `id`=' . "'" . sqlSafeString($viewerid) . "'";
			if (!($result = @$site->execute_query('players', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('There was a problem preventing you from joining the team with id ' . htmlentities($join_team_id));
			}
			
			// update member count of team
			$query = 'UPDATE `teams_overview` SET `member_count`=(SELECT COUNT(*) FROM `players` WHERE `players`.`teamid`=';
			$query .= "'" . sqlSafeString($join_team_id) . "'" . ') WHERE `teamid`=';
			$query .= "'" . sqlSafeString($join_team_id) . "'";
			if (!($result = @$site->execute_query('teams', $query, $connection)))
			{
				$site->dieAndEndPage('There was a problem updating member count for the team with id ' . htmlentities($join_team_id));
			}
			
			echo '<p>You successfully joined the team ' . $team_name . '!</p>';
			
			// check if a invite was used to join
			if ($invite_available)
			{
				// invite was indeed use, delete the invite
				$query = 'DELETE LOW_PRIORITY FROM `invitations` WHERE `teamid`=';
				$query .= "'" . sqlSafeString($join_team_id) . "'";
				$query .= ' AND `invited_playerid`=' . "'" . sqlSafeString($viewerid) . "'";
				if (!$result = $site->execute_query('invitations', $query, $connection))
				{
					$site->dieAndEndPage('Could not delete used invitation for player with id ' . sqlSafeString($viewerid) . '.');
				}
				
			}
		}
		$site->dieAndEndPage();
	}
	
	echo '<div class="simple-paging"><a class="button" href="./">Cancel &amp; back to overview</a></div>' . "\n";
	echo '<div class="static_page_box main-box">' . "\n";
	
	$query = 'SELECT `name` FROM `teams`';
	$query .= ' WHERE `id`=' . sqlSafeStringQuotes($join_team_id);
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	checkNumberRows($result, $site);
	
	$team_name = '(no name)';
	if (mysql_num_rows($result) === 1)
	{
		$allowed_to_join = 0;
		while($row = mysql_fetch_array($result))
		{
			$team_name = $row['name'];
		}
		mysql_free_result($result);
	} else
	{
		$site->dieAndEndPage('There was more than one team with the same id ('. sqlSafeString($join_team_id) . ' or the team did not exist. This should never happen.');
	}
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?join=' . urlencode($join_team_id) . '">' . "\n";
	echo '<div><input type="hidden" name="confirmed" value="1"></div>' . "\n";
	
	$new_randomkey_name = $randomkey_name . microtime();
	$new_randomkey = $site->set_key($new_randomkey_name);
	echo '<div><input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"></div>' . "\n";
	echo '<div><input type="hidden" name="' . htmlentities($randomkey_name) . '" value="';
	echo urlencode(($_SESSION[$new_randomkey_name])) . '"></div>' . "\n";
	echo '<p style="display:inline">Do you really want to join the team ' . $team_name . '?</p>' . "\n";
	echo '<div style="display:inline"><input type="submit" name="join_team" value="Join the team" id="send"></div>' . "\n";
	echo '</form>' . "\n";
	$site->dieAndEndPage('');
}

function reactivateTeam()
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	global $allow_reactivate_teams;
	
	echo '<div class="simple-paging"><a class="button previous" href="./">overview</a></div>' . "\n";
	echo '<div class="static_page_box">' . "\n";
	
	// no anon team deletion
	if ($viewerid < 1)
	{
		echo '<p>You must login to reactivate the team.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	if (!$allow_reactivate_teams)
	{
		echo '<p>You have no permission to reactivate any team</p>';
		$site->dieAndEndPage('');
	}
	
	// get a full list of deleted teams
	// teams_overview.deleted: 0 new; 1 active; 2 deleted; 3 revived
	$query = ('SELECT `teams`.`id`,`teams`.`name` FROM `teams`,`teams_overview`'
			  . ' WHERE (`teams_overview`.`deleted`=' . sqlSafeStringQuotes('2')
			  . ' AND `teams`.`id`=`teams_overview`.`teamid`)'
			  . ' ORDER BY `teams`.`name`');
	if (!($result_teams = @$site->execute_query('teams, teams_overview', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	if ((int) mysql_num_rows($result_teams) < 1)
	{
		// not deleted team yet
		echo '<p>There is not a single deleted team in the database.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	// get a full list of teamless, not deleted players
	// status: active; deleted; disabled; banned
	$query = ('SELECT `id`,`name` FROM `players`'
			  . ' WHERE (`teamid`=' . sqlSafeStringQuotes('0')
			  . ' AND `status`<>' . sqlSafeStringQuotes('deleted') . ')'
			  . ' ORDER BY `name`');
	if (!($result_players = @$site->execute_query('players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	if ((int) mysql_num_rows($result_players) < 1)
	{
		// not deleted team yet
		echo '<p>There is not a single teamless player in the database</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	$profile = 0;
	if (isset($_POST['reactivate_team_id']))
	{
		$profile = (int) $_POST['reactivate_team_id'];
	}
	$leader_profile = 0;
	if (isset($_POST['reactivate_team_new_leader_id']))
	{
		$leader_profile = (int) $_POST['reactivate_team_new_leader_id'];
	}
	if (($profile > 0) && ($leader_profile > 0))
	{
		// team to be revived has been chosen
		
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);			
		if (!($randomkeysmatch))
		{
			echo '<p>The key did not match. It looks like you came from somewhere else.</p>';
			$site->dieAndEndPage('');
		}
		
		// no further information about total deleted teams and total teamless players needed
		mysql_free_result($result_players);
		mysql_free_result($result_teams);
		
		// check if the team is deleted
		$query = 'SELECT `teams`.`id`,`teams`.`name` FROM `teams`,`teams_overview`';
		$query .= ' WHERE (`teams`.`id`=' . sqlSafeString(htmlentities($profile));
		$query .= ' AND `teams_overview`.`deleted`=' . sqlSafeStringQuotes('2');
		$query .= ' AND `teams`.`id`=`teams_overview`.`teamid`)';
		if (!($result_teams = @$site->execute_query('teams, teams_overview', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		if (((int) mysql_num_rows($result_teams) < 1) || ((int) mysql_num_rows($result_teams) > 1))
		{
			// not deleted team yet
			echo '<p class="first_p">There must be exactly one deleted team spefified to revive a team</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		// check if the new team leader is teamless
		$query = 'SELECT `id`,`name` FROM `players`';
		$query .= ' WHERE (`id`=' . "'" . sqlSafeString(htmlentities($leader_profile)) . "'";
		$query .= ' AND `teamid`=' . "'" . sqlSafeString('0') . "'";
		$query .= ' AND `status`<>' . sqlSafeStringQuotes('deleted') . ')';
		if (!($result_players = @$site->execute_query('players', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		if (((int) mysql_num_rows($result_players) < 1) || ((int) mysql_num_rows($result_players) > 1))
		{
			// not deleted team yet
			echo '<p class="first_p">There must be exactly one teamless player spefified to revive a team</p>' . "\n";
			$site->dieAndEndPage('');
		}
		// initalise variable with error, will be overwritten if a name was found
		$username = '(error: no username found)';
		while($row = mysql_fetch_array($result_players))
		{
			$username = $row['name'];
		}
		
		// reactivate the team
		$query = 'UPDATE `teams_overview` SET `deleted`=' . sqlSafeStringQuotes('1');
		$query .= ',`member_count`=' . sqlSafeStringQuotes('1');
		$query .= ' WHERE `teamid`=' . "'" . sqlSafeString($profile) . "'";
		if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
		{
			$site->dieAndEndPage('The team with id ' . sqlSafeString($profile) .
								 ' could not be revived by player with id ' . sqlSafeString($viewerid). '!');
		}
		
		// set the user's new teamid
		$query = 'UPDATE `players` SET `teamid`=' . "'" . sqlSafeString($profile) . "'";
		$query .= ' WHERE `id`=' . "'" . sqlSafeString($leader_profile). "'";
		if (!($result = @$site->execute_query('players', $query, $connection)))
		{
			$site->dieAndEndPage('The player with id ' . sqlSafeString($leader_profile) .
								 ' could not be set to be a member of team with id ' .
								 sqlSafeString($profile). ' by player with id ' . sqlSafeString($viewerid). '!');
		}
		
		// all done
		echo '<p class="first_p">The team was successfully reactivated with ' . htmlent($username) . ' as leader!</p>' . "\n";
		$site->dieAndEndPage('');
	}
	
	
	$team_name_list = Array();
	$team_id_list = Array();
	while($row = mysql_fetch_array($result_teams))
	{
		$team_name_list[] = $row['name'];
		$team_id_list[] = $row['id'];
	}
	mysql_free_result($result_teams);
	
	$list_team_id_and_name = Array();
	
	$list_team_id_and_name[] = $team_id_list;
	$list_team_id_and_name[] = $team_name_list;
	
	$n = ((int) count($team_id_list)) - 1;
	
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="./?reactivate">' . "\n";
	echo '<p><label for="reactivate_team">Select the team to be reactivated:</label>' . "\n";
	echo '<span><select id="reactivate_team" name="reactivate_team_id">' . "\n";
	
	$n = ((int) count($team_id_list)) - 1;
	for ($i = 0; $i <= $n; $i++)
	{
		echo '<option value="';
		// no htmlentities because team id 0 is reserved
		echo $list_team_id_and_name[0][$i];
		if (isset($leader_of_team_with_id) && ((int) $list_team_id_and_name[0][$i] === $leader_of_team_with_id))
		{
			echo '" selected="selected';
		}
		echo '">' . $list_team_id_and_name[1][$i];
		echo '</option>' . "\n";
	}
	
	echo '</select></span>' . "\n";
			
	echo '<p><label for="reactivate_team_new_leader">Select the new team leader:</label>' . "\n";
	echo '<span><select id="reactivate_team_new_leader" name="reactivate_team_new_leader_id">' . "\n";
	$player_name_list = Array();
	$player_id_list = Array();
	while($row = mysql_fetch_array($result_players))
	{
		$player_name_list[] = $row['name'];
		$player_id_list[] = $row['id'];
	}
	mysql_free_result($result_players);
	
	$list_player_id_and_name = Array();
	
	$list_player_id_and_name[] = $player_id_list;
	$list_player_id_and_name[] = $player_name_list;
	
	$n = ((int) count($player_id_list)) - 1;
	for ($i = 0; $i <= $n; $i++)
	{
		echo '<option value="';
		// no htmlentities because team id 0 is reserved
		echo $list_player_id_and_name[0][$i];
		echo '">' . $list_player_id_and_name[1][$i];
		echo '</option>' . "\n";
	}
	
	echo '</select></span>' . "\n";		
	
	// protection against links
	$new_randomkey_name = $randomkey_name . microtime();
	$new_randomkey = $site->set_key($new_randomkey_name);
	echo '<div>';
	$site->write_self_closing_tag('input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"');
	echo '</div>' . "\n";
	echo '<div>';
	$site->write_self_closing_tag('input type="hidden" name="' . htmlentities($randomkey_name) . '" value="'
								  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
	echo '</div>' . "\n";
	
	echo '<div style="display:inline">';
	$site->write_self_closing_tag('input type="submit" name="reactivate_team" value="Reactivate the team" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";		
	
	$site->dieAndEndPage('');
	
}

function createTeam_step1()
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	
	echo '<div class="simple-paging">';
	echo '<a class="button" href="./">Cancel &amp; back to overview</a>' . "\n";
	echo '</div>';
	
	echo '<div class="static_page_box">' . "\n";
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?create">' . "\n";
	echo '<div>';
	$site->write_self_closing_tag('input type="hidden" name="confirmed" value="1"');
	echo '</div>' . "\n";
	
	$new_randomkey_name = $randomkey_name . microtime();
	$new_randomkey = $site->set_key($new_randomkey_name);
	echo '<div>';
	$site->write_self_closing_tag('input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"');
	echo '</div>' . "\n";
	echo '<div>';
	$site->write_self_closing_tag('input type="hidden" name="' . htmlentities($randomkey_name) . '" value="'
								  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
	echo '</div>' . "\n";
	
	// team name
	echo '<p><label class="team_change" for="edit_team_name">Team name: </label>' . "\n";
	$site->write_self_closing_tag('input type="text" maxlength="30" size="30" name="edit_team_name" value="enter team name here" id="edit_team_name"'
								  . ' onfocus="if(this.value==' . "'" . 'enter team name here' . "'" . ') this.value=' . "'" . "'" . '"'
								  . ' onblur="if(this.value==' . "'" . "'" . ') this.value=' . "'" . 'enter team name here' . "'" . '"');
	echo '</p>' . "\n";
	
	// team leader is automatically the creator of the team
	
	// any teamless player allowed to join? default to yes.
	echo '<p><label class="team_change" for="any_teamless_player_can_join">Can any teamless player join the team?</label> ';
	$site->write_self_closing_tag('input type="checkbox" name="any_teamless_player_can_join" value="'
								  . '1' . '" id="any_teamless_player_can_join" checked="checked"');
	echo '</p>' . "\n";
	
	// team description
	if ($site->bbcode_lib_available())
	{
		echo "\n" . '<div class="team_change" id="bbcode_buttons1" style="display: inline-block;">';
		echo '<div class="invisi" style="display: inline;">' . "\n";
		echo '	<label class="team_change">bbcode:</label></div>' . "\n";
		echo '<span class="bbcode_buttons">';
		include dirname(dirname(__FILE__)) . '/CMS/bbcode_buttons.php';
		$bbcode = new bbcode_buttons();
		// set up name of field to edit so javascript knows which element to manipulate
		$bbcode->showBBCodeButtons('team_description');
		unset($bbcode);
		echo '</span>';
		echo "\n";
		echo '</div>' . "\n";
	}
	echo '<div><label class="team_change" for="team_description">Edit team description: </label><span><textarea id="team_description" rows="10" cols="50" name="team_description">';
	if (isset($team_description))
	{
		echo $team_description;
	} else
	{
		echo 'Think of a good description';
	}
	echo '</textarea></span></div>' . "\n";
	
	// logo/avatar url
	echo '<p><label class="team_change" for="edit_avatar_url">Avatar URL: </label>';
	
	// quell warning about not initialised variable
	if (!(isset($logo_url)))
	{
		$logo_url = '';
	}
	$site->write_self_closing_tag('input id="edit_avatar_url" type="text" name="logo_url" maxlength="200" size="60" value="'.$logo_url.'"');
	echo '</p>';
	
	echo '<div>';
	$site->write_self_closing_tag('input type="submit" name="edit_team_data" value="Submit new team creation" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	$site->dieAndEndPage('');
	
}

function createTeam_step2()
{
	global $site;
	global $connection;
	global $randomkey_name;
	global $viewerid;
	
	echo '<div class="simple-paging"><a class="previous" href="./">overview</a></div>' . "\n";
			
	echo '<div class="main-box">' . "\n";
	// someone is trying to break the form
	// TODO: implement preview
	if (($_POST['confirmed'] < 1) || ($_POST['confirmed'] > 2))
	{
		$site->dieAndEndPage('Your (id='. sqlSafeString($viewerid) . ') attempt to insert wrong data into the form was detected.');
	}
	
	$new_randomkey_name = '';
	if (isset($_POST['key_name']))
	{
		$new_randomkey_name = html_entity_decode($_POST['key_name']);
	}
	$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);						
	if (!($randomkeysmatch))
	{
		echo '<p class="message">The key did not match. It looks like you came from somewhere else.</p>';
		$site->dieAndEndPage('');
	}
	
	// only teamless players can create a new team
	$query = 'SELECT `teamid` FROM `players` WHERE `id`=';
	$query .= sqlSafeStringQuotes($viewerid);
	$query .= ' AND `teamid`=' . "'" . '0' . "'" . ' LIMIT 1';
	if (!($result = @$site->execute_query('players, teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// result must be only one row
	checkNumberRows($result, $site);
	$teamless_player_wants_to_create_a_team = false;
	while($row = mysql_fetch_array($result))
	{
		// this should always be true due to the query
		// do the check nevertheless because it might give some extra security
		if ((int) $row['teamid'] === 0)
		{
			$teamless_player_wants_to_create_a_team = true;
		}
	}
	mysql_free_result($result);
	
	if (!($teamless_player_wants_to_create_a_team))
	{
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') can not create a new team because you are already member of a team.');
	}
	
	// check for team name
	if (!(isset($_POST['edit_team_name'])))
	{
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') did not specify a team name while creating a new team.');

	}
	
	// do not allow "(teamless)" as team name (refers to all teamless players)
	$name = cleanTeamName($_POST['edit_team_name']);
	if ($name === false)
	{
		// team name not clean -> do not create team with its name
		$site->dieAndEndPage('You (id=' . sqlSafeString($viewerid) . ') tried to create a new team a with non allowed team name.'
							 . ' A team name must not end with whitespace and must not contain non-printable characters'
							 . ' as well as being not equal to the reserved name "(teamless)".');
		
	}
	
	// is the team name already used?
	$query = 'SELECT `name` FROM `teams` WHERE `name`=' . sqlSafeStringQuotes(htmlent($name)) . ' LIMIT 1';
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	if ((int) mysql_num_rows($result) > 0)
	{
		mysql_free_result($result);
		// team name already used -> do not create team with that name
		echo '<p class="message">The team was not created because there is already a team with that name in the database.</p>' . "\n";
		$site->dieAndEndPage('');
	}
	mysql_free_result($result);
	
	// is the player name already used?
	$query = 'SELECT `teamid` FROM `players` WHERE `id`=' . "'" . sqlSafeString($viewerid) . "'" . ' LIMIT 1';
	if (!($result = @$site->execute_query('players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	if ((int) mysql_num_rows($result) > 0)
	{
		$tmp_team_id = (int) 0;
		while($row = mysql_fetch_array($result))
		{
			$tmp_team_id = $row['teamid'];
		}				
		if ($tmp_team_id <> 0)
		{
			mysql_free_result($result);
			// the supposed leader is already in a team -> do not create team with him as leader
			echo '<p class="message">The team was not created because the team leader already belongs to a team.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		unset($tmp_team_id);
	}
	mysql_free_result($result);
	
	// create the team itself
	$query = 'INSERT INTO `teams` (`name`, `leader_playerid`) VALUES (' . sqlSafeStringQuotes($name) . ', ' . sqlSafeStringQuotes($viewerid) . ')';
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	$query = 'SELECT `id` FROM `teams` WHERE `leader_playerid`=' . sqlSafeStringQuotes($viewerid);
	if (!($result = @$site->execute_query('teams', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// result must be only one row
	checkNumberRows($result, $site);
	$new_team_id = -1;
	while($row = mysql_fetch_array($result))
	{
		$new_team_id = (int) $row['id'];
	}
	mysql_free_result($result);
	
	if (($new_team_id === (-1)) || ($new_team_id === 0))
	{
		$site->dieAndEndPage('Error: The team created by player (id=' . sqlSafeString($viewerid) . ') has no team id (id=' . sqlSafeString($new_team_id) . ').');
	}
	
	// set up new team permissions
	$query = 'INSERT INTO `teams_permissions` (`teamid`) VALUES (' . "'" . sqlSafeString($new_team_id) . "'" . ')';
	if (!($result = @$site->execute_query('teams_permissions', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// set up new team overview
	$any_teamless_player_can_join = '0';
	if (isset($_POST['any_teamless_player_can_join']))
	{
		$any_teamless_player_can_join = '1';
	}
	$query = 'INSERT INTO `teams_overview` (`teamid`, `any_teamless_player_can_join`) VALUES (' . "'" . sqlSafeString($new_team_id) . "'" . ', ' . "'";
	$query .= sqlSafeString($any_teamless_player_can_join) . "'" . ')';
	if (!($result = @$site->execute_query('teams_overview', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// set up team profile
	// set the date and time (for team creation timestamp)
	date_default_timezone_set($site->used_timezone());
	$query = 'INSERT INTO `teams_profile` (`teamid`, `description`, `raw_description`, `created`) VALUES (' . sqlSafeStringQuotes($new_team_id) . ', ';
	$query .= sqlSafeStringQuotes($site->bbcode($_POST['team_description'])) . ', ' . sqlSafeStringQuotes($_POST['team_description']);
	$query .= ', ' . sqlSafeStringQuotes(date('Y-m-d')) . ')';
	if (!($result = @$site->execute_query('players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// the founder is now member of the team
	$query = 'UPDATE `players` SET `teamid`=' . "'" . sqlSafeString($new_team_id) . "'";
	$query .= ' WHERE `id`=' . "'" . sqlSafeString($viewerid) . "'";
	if (!($result = @$site->execute_query('players', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('');
	}
	
	// write logo
	writeLogo();
	
	echo '<p class="message">Your new team was created successfully.</p>';
	$site->dieAndEndPage('');
	
	
}

function cleanTeamName($name)
	{
		// check for printable characters
		if (ctype_print($name))
		{
			// strip whitespace or other characters from the end of the name
			$cleaned_name = rtrim($name);
			// "(teamless)" is a reserved name
			if (strcasecmp($_POST['edit_team_name'], '(teamless)') === 0)
			{
				$cleaned_name = '';
			}
			
			// check if cleaned team name is different than user entered team name
			if (strcmp($name, $cleaned_name) === 0)
			{
				return htmlent($name);
			}
		}
		return false;
	}
	
function writeLogo()
{
	global $profile;
	global $connection;
	global $site;
	
	if (isset($_POST['logo_url']))
	{
		$allowedExtensions = array('.png', '.bmp', '.jpg', '.gif', 'jpeg');
		$logo_url = sqlSafeString($_POST['logo_url']);
		if ((in_array(substr($logo_url, -4), $allowedExtensions)) && (substr($logo_url, 0, 7) == 'http://'))
		{
			// image url exists and has a valid file extension
			$query = 'UPDATE `teams_profile` SET `logo_url`=' . sqlSafeStringQuotes($logo_url);
			$query .= ' WHERE `teamid`=' . sqlSafeStringQuotes($profile);
			if (!($result = $site->execute_query('teams_profile', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
		} else
		{
			if (!(strcmp(($_POST['logo_url']), '') === 0))
			{
				echo '<p>Error: Skipping logo setting: Not allowed URL or extension.</p>';
			}
		}
	}
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

function checkNumberRows($result, $site)
{				
	if (mysql_num_rows($result) > 1)	
	{
		$site->dieAndEndPage('Error: There was more than one user or team with the same id. This should never happen.');
	}
}
	

// sorting callback function
function cmp($a, $b)
{
	global $orderAsc;
	global $sortBy;
	global $sortByName;
	
	$sortBy = strtolower($sortBy);
	if (!($sortByName))
	{
		if ($orderAsc)
		{
			return (intval($a[$sortBy]) >= intval($b[$sortBy]));
		} else
		{
			return (intval($a[$sortBy]) < intval($b[$sortBy]));					
		}
	}

	if ($orderAsc)
	{
		
		return strcmp(strtolower($a[$sortBy]), strtolower($b[$sortBy]));
	} else
	{
		return (strcmp(strtolower($b[$sortBy]), strtolower($a[$sortBy])));
	}
}

// write one specified table header
function writeNavi($item, $profile, $order, $orderLink)
{
	global $sortBy;
	
	$itemLink = str_replace(' ', '%20', $item);
	echo ('	<th><a href="./?opponent_stats=' . $profile
		  . '&amp;sort=' . $itemLink . '&amp;order=');
	if (strcmp($sortBy, $item) === 0)
	{
		echo $orderLink . '">' . $item;
	} else
	{
		echo $order . '">' . $item;
	}
	
	if (strcmp($sortBy, $item) === 0)
	{
		echo ' <span class="opponent_stats_order_' . $order . '">(' . $order . ')</span>';
	}
	echo '</a></th>' . "\n";
}


function getMonth($monthnum, $startyear) 
{
	$month = $monthnum % 12;
	$year = '';
	if ($month === 6) 
	{
		$year = '<br/>' . (floor($monthnum / 12) + $startyear);
	}
	return $month . $year;
}



?>