<?php
	set_time_limit(0);
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	
	$display_page_title = 'BBCode libary updater';
	require_once (dirname(__FILE__) . '/web/CMS/index.inc');
	
	if (!isset($site))
	{
		require_once (dirname(__FILE__) . '/web/CMS/siteinfo.php');
		$site = new siteinfo();
	}
	
	if (!isset($connection))
	{
		$connection = $site->connect_to_db();
	}
	
	$randomkey_name = 'randomkey_user';
	$viewerid = intval(getUserID());
	
	if ($viewerid < 1)
	{
		echo '<p class="first_p">You need to be logged in to update the old bbcode entries.</p>';
		$site->dieAndEndPage();
	}
	
	if (!(isset($_SESSION['IsAdmin'])) || !($_SESSION['IsAdmin']))
	{
		$site->dieAndEndPage('User with id ' . sqlSafeStringQuotes($viewerid) . ' tried to run the bbcode library updater script without permissions.');
	}
	
	$db_from = new db_import;
	$db_to_be_imported = $db_from->db_import_name();
	
	// this script will update all entries in the database that are including any fields that could be created by using a bbcode library
	// it does not detect if the old entries were created by using raw (X)HTML, instead it will just output these entries using the bbcode lib
	// the strong recommendation is to always use a bbcode library
	
	// players
	function update_players()
	{
		global $site;
		global $connection;
		
		$query = 'SELECT `id`, `raw_user_comment`, `raw_admin_comments` from `players_profile`';
		if (!($result = @$site->execute_query('players_profile', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		while ($row = mysql_fetch_array($result))
		{
			// skip empty entries
			if (!(strcmp($row['raw_user_comment'], '') === 0))
			{
				// is user already added to db?
				// callsigns are case treated insensitive
				$query = ('UPDATE `players_profile` SET `user_comment`=' . sqlSafeStringQuotes($site->bbcode($row['raw_user_comment']))
						  . ' WHERE `id`=' . sqlSafeStringQuotes($row['id']) . ' LIMIT 1');
				// execute query, ignore result
				@$site->execute_query('players_profile', $query, $connection);
			}
			if (!(strcmp($row['raw_admin_comments'], '') === 0))
			{
				// is user already added to db?
				// callsigns are case treated insensitive
				$query = ('UPDATE `players_profile` SET `admin_comments`=' . sqlSafeStringQuotes($site->bbcode($row['raw_admin_comments']))
						  . ' WHERE `id`=' . sqlSafeStringQuotes($row['id']) . ' LIMIT 1');
				// execute query, ignore result
				@$site->execute_query('players_profile', $query, $connection);
			}
		}
		
		mysql_free_result($result);
	}
	
	// teams
	function update_teams()
	{
		global $site;
		global $connection;
		
		$query = 'SELECT `id`, `raw_description` from `teams_profile`';
		if (!($result = @$site->execute_query('teams_profile', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($row = mysql_fetch_array($result))
		{
			// skip empty entries
			if (!(strcmp($row['raw_description'], '') === 0))
			{				
				$query = ('UPDATE `players_profile` SET `description`=' . sqlSafeStringQuotes($site->bbcode($row['raw_description']))
						  . ' WHERE `id`=' . sqlSafeStringQuotes($row['id']) . ' LIMIT 1');
				// execute query, ignore result
				@$site->execute_query('teams_profile', $query, $connection);
			}
		}
		mysql_free_result($result);
	}
	
	// news entries
	function update_news()
	{
		global $site;
		global $connection;
		
		$query = 'SELECT `id`, `raw_announcement` from `news`';
		if (!($result = @$site->execute_query($db_to_be_imported, 'news', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($row = mysql_fetch_array($result))
		{
			// skip empty entries
			if (!(strcmp($row['raw_announcement'], '') === 0))
			{				
				$query = ('UPDATE `news` SET `announcement`=' . sqlSafeStringQuotes($site->bbcode($row['raw_announcement']))
						  . ' WHERE `id`=' . sqlSafeStringQuotes($row['id']) . ' LIMIT 1');
				// execute query, ignore result
				@$site->execute_query('news', $query, $connection);
			}
		}
	}
	
	
	// ban entries
	function import_bans()
	{
		global $site;
		global $connection;
		
		$query = 'SELECT `id`, `raw_announcement` from `bans`';
		if (!($result = @$site->execute_query($db_to_be_imported, 'bans', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		while ($row = mysql_fetch_array($result))
		{
			// skip empty entries
			if (!(strcmp($row['raw_announcement'], '') === 0))
			{				
				$query = ('UPDATE `bans` SET `announcement`=' . sqlSafeStringQuotes($site->bbcode($row['raw_announcement']))
						  . ' WHERE `id`=' . sqlSafeStringQuotes($row['id']) . ' LIMIT 1');
				// execute query, ignore result
				@$site->execute_query('news', $query, $connection);
			}
		}
	}
	
	// now convert the entries using the available functions
	update_players();
	update_teams();
	update_news();
	update_bans();
	// done
?>
<p>Updating all database entries involving BBCode finished!</p>
</div>
</body>
</html>