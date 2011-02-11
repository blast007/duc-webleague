<?php
	set_time_limit(0);
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	
	$display_page_title = 'Webleague DB importer';
	require_once (dirname(__FILE__) . '/CMS/index.inc');
	//	require realpath('../CMS/navi.inc');
	
	if (!isset($site))
	{
		require_once (dirname(__FILE__) . '/CMS/siteinfo.php');
		$site = new siteinfo();
	}
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_user';
	$viewerid = (int) getUserID();
	
	if ($viewerid < 1)
	{
		if (!(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])))
		{
			echo '<p class="first_p">You need to be logged in to update the old bbcode entries.</p>';
			$site->dieAndEndPage();
		}
	} elseif (!(isset($_SESSION['IsAdmin'])) || !($_SESSION['IsAdmin']))
	{
		$site->dieAndEndPage('User with id ' . sqlSafeStringQuotes($viewerid) . ' tried to run the webleague importer script without permissions.');
	}
	
	$db_from = new db_import;
	$db_to_be_imported = $db_from->db_import_name();
	


	// matches
	function import_old_matches()
	{
		global $site;
		global $connection;
		global $db_to_be_imported;
		
		$site->selectDB($site->db_used_name(), $connection);
		$query = 'SELECT id FROM `players` WHERE name like \'osta\'';
		if (!($result = @$site->execute_query('players', $query, $connection)))
		{
			$site->dieAndEndPage('');
		}
		$row = mysql_fetch_array($result);
		$editorid = $row['id'];
		
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `bzl_match` WHERE newrankt1 is null ORDER BY `id`';
		if (!($result = @$site->execute_query('players', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		$suspended_status = 'active';
		while ($row = mysql_fetch_array($result))
		{
			$query = ('INSERT INTO `matches` (`id`,`playerid`,`timestamp`,`team1_teamid`,`team2_teamid`,`team1_points`,`team2_points`,`team1_new_score`,`team2_new_score`,`duration`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['id']));
			//set editor id to some admin
			$query .= ',' . sqlSafeStringQuotes($editorid);
			if (strcmp($row['tsedit'],'') === 0)
			{
				$query .= ',' . sqlSafeStringQuotes($row['tsenter']);
			} else
			{
				$query .= ',' . sqlSafeStringQuotes($row['tsedit']);
			}
			$query .= (',' . sqlSafeStringQuotes($row['team1']) . ',' . sqlSafeStringQuotes($row['team2'])
					   . ',' . sqlSafeStringQuotes($row['score1']) . ',' . sqlSafeStringQuotes($row['score2'])
					   . ',' . sqlSafeStringQuotes(1200) . ',' . sqlSafeStringQuotes(1200)
					   . ',' . sqlSafeStringQuotes($row['mlength']) . ')');
			// execute query, ignore result
			@$site->execute_query('matches', $query, $connection);
		}
		mysql_free_result($result);
	}
	
	
	
	
	
	
	// create lookup array
	$deleted_players = array(array());
	// DUMMY player
	$deleted_players['0']['callsign'] = 'CTF League System';
	$deleted_players['0']['dummy'] = true;
	
	//this should be set with initial import on clear database;
	import_old_matches();
	
	// lookup array no longer needed
	unset($deleted_players);
	
	
	// do maintenance after importing the database to clean it
	// a check inside the maintenance logic will make sure it will be only performed one time per day at max
	require_once('CMS/maintenance/index.php');
	
	// (should take about 3 minutes to import the data until this point)
	// disable this when not doing the final import because this last step would take 90 minutes
	//resolve_visits_log_hosts();
	
	// done
?>
<p>Import finished!</p>
</div>
</body>
</html>