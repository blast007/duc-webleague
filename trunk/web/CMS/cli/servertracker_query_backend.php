<?php
	// this file is only called in the background and inserts current server population data into the database
	
	init();
	echo 'init done' . "\n";
	if (is_update_needed())
	{
		echo 'servers queried' . "\n";
		query_servers();
	}
	echo 'updated' . "\n";
	
	function init()
	{
		global $site;
		global $connection;
		
		require_once (dirname(dirname(__FILE__)) . '/siteinfo.php');
		$site = new siteinfo();
		
		$connection = $site->connect_to_db();
	}
	
	function unlock_table()
	{
		global $site;
		global $connection;
		
		global $tables_locked;
		
		$query = 'UNLOCK TABLES';
		if (!($site->execute_query('all!', $query, $connection)))
		{
			die('Unfortunately unlocking tables failed. This likely leads to an access problem to database!');
		}
		$query = 'COMMIT';
		if (!($site->execute_query('all!', $query, $connection)))
		{
			die('Unfortunately committing changes failed!');
		}
		$query = 'SET AUTOCOMMIT = 1';
		if (!($result = @$site->execute_query('all!', $query, $connection)))
		{
			die('Trying to activate autocommit failed.');
		}
	}
	
	function lock_table()
	{
		global $site;
		global $connection;
		
		$query = 'LOCK TABLES `misc_data` WRITE';
		if (!($result = @$site->execute_query('misc_data', $query, $connection)))
		{
			unlock_table();
			die('Unfortunately locking the matches table failed and thus entering the match was cancelled.');
		}
		$query = 'SET AUTOCOMMIT = 0';
		if (!($result = @$site->execute_query('all!', $query, $connection)))
		{
			unlock_table();
			die('Trying to deactivate autocommit failed.');
		}
	}
	
	function is_update_needed()
	{
		global $site;
		global $connection;
		
		$query = ('SELECT `last_servertracker_query` FROM `misc_data`'
				  . ' LIMIT 1');
		if (!($result = @$site->execute_query('misc_data', $query, $connection)))
		{
			die('Could not find out when last query occured.');
		}
		
		// read out the date of last update
		while ($row = mysql_fetch_array($result))
		{
			$last_query = $row['last_servertracker_query'];
		}
		mysql_free_result($result);
		
		// is last update older than 150 seconds?
		$current_time = time();
		if ($current_time - intval($last_query) < 150)
		{
			// no -> silently abort
			die();
		}
		
		lock_table();
		$query = 'UPDATE `misc_data` SET `last_servertracker_query`=' . sqlSafeStringQuotes($current_time);
		if (!($result = @$site->execute_query('misc_data', $query, $connection)))
		{
			die('Could not set newest last_servertracker_query value.');
		}
		unlock_table();
		
		return true;
	}
	
	function query_servers()
	{
		global $site;
		global $connection;
		
		$query = ('SELECT `id`, `servername`, `serveraddress` FROM `servertracker`'
				  . ' WHERE `type` = \'match\' ORDER BY `id`');
		if (!($result = $site->execute_query('servertracker', $query, $connection)))
		{
			die('Could not find out servername and serveraddress to be updated.');
		}
		
		// need to include game specific backend
		include (dirname(dirname(dirname(__FILE__))) . "/Servertracker/bzfquery.php");
		
		// update each entry
		while ($row = mysql_fetch_array($result))
		{
			// get raw query result
			$data = bzfquery($row['serveraddress']);
			
			// build the query with the result
			$query = ('UPDATE `servertracker` SET'
					  . ' `cur_players_total`=' . sqlSafeStringQuotes($data['numPlayers'])
					  . ' WHERE `id`=' . sqlSafeStringQuotes($row['id'])
					  . ' LIMIT 1');
			
			// execute the update query
			@$site->execute_query('servertracker', $query, $connection);
		}
		mysql_free_result($result);
		
//		$query = 'UPDATE `misc_data` SET `last_servertracker_query`=' . sqlSafeStringQuotes($current_time);
//		if (!($result = @$site->execute_query('misc_data', $query, $connection)))
//		{
//			die('Could not set newest last_servertracker_query value.');
//		}
		
	}
?>