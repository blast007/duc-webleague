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
	
//	this code does not work because the order of statements in the dump
//	cause the relations in the final database being violated
//	$file = dirname(__FILE__) . '/ts-CMS_structure.sql';
//	if (file_exists($file) && is_readable($file))
//	{
//		$output_buffer = '';
//		ob_start();
//		
//		readfile($file);
//		$output_buffer .= ob_get_contents();
//		ob_end_clean();
//	} else
//	{
//		echo '<p>The file at ' . htmlent($file) . ' does not exist or is not readable</p>' . "\n";
//		return;
//	}
//	
//	$output_buffer = explode("\n", $output_buffer);
//	$db_structure_calls = '';
//	foreach($output_buffer as $one_line)
//	{
//		if (!(strcmp(substr($one_line,0,1), '#') === 0))
//		{
//			$db_structure_calls .= $one_line;
//		}
//	}
//	
//	$output_buffer = explode(';', $db_structure_calls);
//	$db_structure_calls = array();
//	foreach($output_buffer as $one_line)
//	{
//		if (!(strcmp(substr($one_line,0,2), '/*') === 0))
//		{
//			$db_structure_calls[] = $one_line;
//		}
//	}
//	
//	echo '<pre>';
//	print_r($db_structure_calls);
//	echo '</pre>';
//	foreach($db_structure_calls as $one_call)
//	{
//		@$site->execute_query('all!', $one_call, $connection);
//	}
	
	
	
	function clear_current_tables()
	{
		
		global $site;
		global $connection;
		
		$site->selectDB($site->db_used_name(), $connection);
		$query = 'DELETE FROM `bans`';
		// execute query, ignore result
		$site->execute_query('bans', $query, $connection);
		
		$query = 'DELETE FROM  `countries` ';
		// execute query, ignore result
		$site->execute_query('countries', $query, $connection);
		
		$query = 'DELETE FROM  `invitations` ';
		// execute query, ignore result
		$site->execute_query('invitations', $query, $connection);
		
		$query = 'DELETE FROM  `matches` ';
		// execute query, ignore result
		$site->execute_query('matches', $query, $connection);
		
		$query = 'DELETE FROM  `matches_edit_stats` ';
		// execute query, ignore result
		$site->execute_query('matches_edit_stats', $query, $connection);
		
		$query = 'DELETE FROM  `messages_storage` ';
		// execute query, ignore result
		$site->execute_query('messages_storage', $query, $connection);
		
		$query = 'DELETE FROM  `messages_users_connection` ';
		// execute query, ignore result
		$site->execute_query('messages_users_connection', $query, $connection);
		
		$query = 'DELETE FROM  `news` ';
		// execute query, ignore result
		$site->execute_query('news', $query, $connection);
		
		$query = 'DELETE FROM  `online_users` ';
		// execute query, ignore result
		$site->execute_query('online_users', $query, $connection);
		
		$query = 'DELETE FROM  `players` ';
		// execute query, ignore result
		$site->execute_query('players', $query, $connection);
		
		$query = 'DELETE FROM  `players_profile` ';
		// execute query, ignore result
		$site->execute_query('players_profile', $query, $connection);
		
		$query = 'DELETE FROM `teams` ';
		// execute query, ignore result
		$site->execute_query('teams', $query, $connection);
		
		$query = 'DELETE FROM  `teams_overview` ';
		// execute query, ignore result
		$site->execute_query('teams_overview', $query, $connection);
		
		$query = 'DELETE FROM  `teams_permissions` ';
		// execute query, ignore result
		$site->execute_query('teams_permissions', $query, $connection);
		
		$query = 'DELETE FROM  `teams_profile` ';
		// execute query, ignore result
		$site->execute_query('teams_profile', $query, $connection);
		
		$query = 'DELETE FROM `visits` ';
		// execute query, ignore result
		$site->execute_query('visits', $query, $connection);
		
		$query = 'DELETE FROM  `seasons` ';
		// execute query, ignore result
		$site->execute_query('seasons', $query, $connection);
		
	}
	
	// reset auto-increment values of each table
	function reset_auto_increment()
	{
		global $site;
		global $connection;
		
		$site->selectDB($site->db_used_name(), $connection);
		$query = 'ALTER TABLE `bans` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('bans', $query, $connection);
		
		$query = 'ALTER TABLE `countries` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('countries', $query, $connection);
		
		$query = 'ALTER TABLE `invitations` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('invitations', $query, $connection);
		
		$query = 'ALTER TABLE `matches` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('matches', $query, $connection);
		
		$query = 'ALTER TABLE `matches_edit_stats` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('matches_edit_stats', $query, $connection);
		
		$query = 'ALTER TABLE `messages_storage` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('messages_storage', $query, $connection);
		
		$query = 'ALTER TABLE `messages_users_connection` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('messages_users_connection', $query, $connection);
		
		$query = 'ALTER TABLE `news` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('news', $query, $connection);
		
		$query = 'ALTER TABLE `online_users` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('online_users', $query, $connection);
		
		$query = 'ALTER TABLE `players` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('players', $query, $connection);
		
		$query = 'ALTER TABLE `players_profile` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('players_profile', $query, $connection);
		
		$query = 'ALTER TABLE `static_pages` AUTO_INCREMENT = 1';
		// execute query, ignore result
	//	$site->execute_query('static_pages', $query, $connection);
		
		$query = 'ALTER TABLE `teams` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('teams', $query, $connection);
		
		$query = 'ALTER TABLE `teams_overview` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('teams_overview', $query, $connection);
		
		$query = 'ALTER TABLE `teams_permissions` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('teams_permissions', $query, $connection);
		
		$query = 'ALTER TABLE `teams_profile` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('teams_profile', $query, $connection);
		
		$query = 'ALTER TABLE `visits` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('visits', $query, $connection);
		
		$query = 'ALTER TABLE `seasons` AUTO_INCREMENT = 1';
		// execute query, ignore result
		$site->execute_query('seasons', $query, $connection);
		
	}
	
	// players
	function import_players()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT `id`,`callsign`,`created` FROM `l_player` ORDER BY `id`';
		if (!($result = @$site->execute_query('l_player', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		// 0 means active player
		$suspended_status = 'active';
		
		$index_num = 1;
		$players = array();
		while ($row = mysql_fetch_array($result))
		{
			$current_name = '(no name)';
			// skip deleted users as they can be several times in the db
			// player got deleted, keep track of him
			if (!(strcmp(substr($row['callsign'],-10), ' (DELETED)') === 0))
			{
				$current_name = htmlent($row['callsign']);
			} else
			{
				$current_name = htmlent(substr($row['callsign'],0,-10));
			}
			
			// no empty usernames allowed
			if (!(strcmp($current_name, '') === 0))
			{
				// is user already added to db?
				// callsigns are case treated insensitive
				if (!isset($players[strtolower($current_name)]))
				{
					$site->selectDB($db_to_be_imported, $connection);
					$query = ('SELECT `team`,`last_login`,`comment`,`logo`,`md5password`,`utczone`'
							  . ' FROM `l_player` WHERE `l_player`.`callsign`='
							  . sqlSafeStringQuotes($current_name)
							  . ' LIMIT 1');
					if (!($tmp_result = @$site->execute_query('l_team', $query, $connection)))
					{
						$site->selectDB($site->db_used_name(), $connection);
						$site->dieAndEndPage();
					}
					$site->selectDB($site->db_used_name(), $connection);
					$last_login = '';
					$team = (int) 0;
					$comment = '';
					$logo = '';
					$timezone = (int) 0;
					while ($tmp_row = mysql_fetch_array($tmp_result))
					{
						$last_login = $tmp_row['last_login'];
						$team = $tmp_row['team'];
						$comment = $site->linebreaks($tmp_row['comment']);
						$logo = $tmp_row['logo'];
						$timezone = $tmp_row['utczone'];
						$md5password = $tmp_row['md5password'];
					}
					mysql_free_result($tmp_result);
					
					
					// take care of deleted players
					$site->selectDB($db_to_be_imported, $connection);
					$query = ('SELECT `last_login`, (SELECT COUNT(*) FROM `l_player` WHERE `callsign`='
							  . sqlSafeStringQuotes($current_name) . ' LIMIT 1) AS `num_not_deleted`'
							  . ' FROM `l_player` WHERE `l_player`.`callsign`='
							  . sqlSafeStringQuotes($current_name . ' (DELETED)')
							  . ' ORDER BY `last_login` DESC LIMIT 1');
					if (!($tmp_result = @$site->execute_query('l_team', $query, $connection)))
					{
						$site->selectDB($site->db_used_name(), $connection);
						$site->dieAndEndPage();
					}
					$site->selectDB($site->db_used_name(), $connection);
					while ($tmp_row = mysql_fetch_array($tmp_result))
					{
						if (strcmp($last_login, '') === 0)
						{
							$last_login = $tmp_row['last_login'];
						}
						if ((int) $tmp_row['num_not_deleted'] === 0)
						{
							// set password to empty..you can not expect them to know the old password
							$md5password = '';
						}
					}
					
					
					$query = ('INSERT INTO `players` (`id`,`teamid`,`name`,`status`)'
							  . ' VALUES '
							  . '(' . sqlSafeStringQuotes($index_num) . ',' . sqlSafeStringQuotes($team)
							  . ',' . sqlSafeStringQuotes($current_name) . ',' . sqlSafeStringQuotes($suspended_status)
							  . ')');
					// execute query, ignore result
					$site->execute_query('players', $query, $connection);
					
					$query = ('INSERT INTO `players_profile` (`playerid`,`UTC`,`user_comment`,`raw_user_comment`,`joined`,`last_login`,`logo_url`)'
							  . ' VALUES '
							  . '(' . sqlSafeStringQuotes($index_num) . ',' . sqlSafeStringQuotes($timezone)
							  . ',' . sqlSafeStringQuotes(utf8_encode($comment)) . ',' . sqlSafeStringQuotes(utf8_encode($comment))
							  . ',' . sqlSafeStringQuotes($row['created']) . ',' . sqlSafeStringQuotes($last_login)
							  . ',' . sqlSafeStringQuotes($logo)
							  . ')');
					// execute query, ignore result
					@$site->execute_query('players_profile', $query, $connection);
					
					$query = ('INSERT INTO `players_passwords` (`playerid`,`password`,`password_encoding`)'
							  . ' VALUES '
							  . '(' . sqlSafeStringQuotes($index_num)
							  . ',' . sqlSafeStringQuotes($md5password)
							  . ',' . sqlSafeStringQuotes('md5')
							  . ')');
					// execute query, ignore result
					@$site->execute_query('players_profile', $query, $connection);
					
					// mark the user has been added to db
					// callsigns are case treated insensitive
					$players[strtolower($current_name)] = true;
				}
				$deleted_players[$row['id']]['callsign'] = $current_name;
				
				$index_num++;
			}
		}
		unset($players);
		
		mysql_free_result($result);
		
		// build a lookup table to avoid millions of select id from players where name=bla
		foreach($deleted_players AS &$deleted_player)
		{
			$query = 'SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes($deleted_player['callsign']);
			if (!($result = @$site->execute_query('l_player', $query, $connection)))
			{
				// query was bad, error message was already given in $site->execute_query(...)
				$site->dieAndEndPage('');
			}
			while ($row = mysql_fetch_array($result))
			{
				$deleted_player['id'] = (int) $row['id'];
			}
			mysql_free_result($result);
		}
		unset($deleted_player);
	}
	
	
	// teams
	function import_teams()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `l_team` ORDER BY `created`';
		if (!($result = @$site->execute_query('l_team', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		while ($row = mysql_fetch_array($result))
		{
			$query = ('INSERT INTO `teams` (`id`,`name`,`leader_playerid`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['id']) . ',' . sqlSafeStringQuotes(utf8_encode(htmlentities($row['name'], ENT_QUOTES, 'ISO-8859-1'))));
			if (isset($deleted_players[$row['leader']]) && (isset($deleted_players[$row['leader']]['callsign'])) && (!isset($deleted_players[$row['leader']]['dummy'])))
			{
				$query .= ',' . sqlSafeStringQuotes($deleted_players[($row['leader'])]['id']);
			} else
			{
				$query .= ',' . sqlSafeStringQuotes('0');
			}
			$query .= ')';
			// execute query, ignore result
			@$site->execute_query('teams', $query, $connection);
			
			$activity_status = 1;
			if (strcmp($row['status'], 'deleted') === 0)
			{
				$activity_status = 2;
			}
			$query = ('INSERT INTO `teams_overview` (`teamid`,`score`,`member_count`,`any_teamless_player_can_join`,`deleted`,`num_matches_played`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['id']) . ',' . sqlSafeStringQuotes($row['score'])
					  . ',' . '(SELECT COUNT(*) FROM `players` WHERE `players`.`teamid`=' . sqlSafeStringQuotes($row['id']) . ') '
					  . ',' . sqlSafeStringQuotes($row['adminclosed']) . ',' .sqlSafeStringQuotes($activity_status));
			// total matches
			$site->selectDB($db_to_be_imported, $connection);
			$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
						  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
						  . ') LIMIT 1');
			if (!($tmp_result = @$site->execute_query('bzl_match', $tmp_query, $connection)))
			{
				$site->selectDB($site->db_used_name(), $connection);
				$site->dieAndEndPage('');
			}
			$site->selectDB($site->db_used_name(), $connection);
			while ($tmp_row = mysql_fetch_array($tmp_result))
			{
				$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
			}
			mysql_free_result($tmp_result);
			$query .= ')';
			
			// execute query, ignore result
			@$site->execute_query('teams_overview', $query, $connection);
			
			$activity_status = 1;
			if (!(strcmp($row['active'], 'yes') === 0))
			{
				$activity_status = 0;
			}
			$query = ('INSERT INTO `teams_profile` (`teamid`,`num_matches_won`,`num_matches_draw`,`num_matches_lost`'
					  . ',`description`,`raw_description`, `logo_url`,`created`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['id']));
			
			// matches won
			$site->selectDB($db_to_be_imported, $connection);
			$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
						  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
						  . ') AND ((`team1`=' . sqlSafeStringQuotes($row['id'])
						  . ' AND `score1`>`score2`) OR (`team2`=' . sqlSafeStringQuotes($row['id'])
						  . ' AND `score2`>`score1`)'
						  . ')');
			if (!($tmp_result = @$site->execute_query('bzl_match', $tmp_query, $connection)))
			{
				$site->selectDB($site->db_used_name(), $connection);
				$site->dieAndEndPage('');
			}
			$site->selectDB($site->db_used_name(), $connection);
			while ($tmp_row = mysql_fetch_array($tmp_result))
			{
				$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
			}
			mysql_free_result($tmp_result);
			
			// matches draw
			$site->selectDB($db_to_be_imported, $connection);
			$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
						  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
						  . ') AND (`score1`=`score2`'
						  . ')');
			if (!($tmp_result = @$site->execute_query('bzl_match', $tmp_query, $connection)))
			{
				$site->selectDB($site->db_used_name(), $connection);
				$site->dieAndEndPage('');
			}
			$site->selectDB($site->db_used_name(), $connection);
			while ($tmp_row = mysql_fetch_array($tmp_result))
			{
				$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
			}
			mysql_free_result($tmp_result);
			
			// matches won
			$site->selectDB($db_to_be_imported, $connection);
			$tmp_query = ('SELECT COUNT(*) AS `num_matches` FROM `bzl_match` WHERE (`team1`='
						  . sqlSafeStringQuotes($row['id']) . ' OR `team2`=' . sqlSafeStringQuotes($row['id'])
						  . ') AND ((`team1`=' . sqlSafeStringQuotes($row['id'])
						  . ' AND `score1`<`score2`) OR (`team2`=' . sqlSafeStringQuotes($row['id'])
						  . ' AND `score2`<`score1`)'
						  . ')');
			if (!($tmp_result = @$site->execute_query('bzl_match', $tmp_query, $connection)))
			{
				$site->selectDB($site->db_used_name(), $connection);
				$site->dieAndEndPage('');
			}
			$site->selectDB($site->db_used_name(), $connection);
			while ($tmp_row = mysql_fetch_array($tmp_result))
			{
				$query .= ', ' . sqlSafeStringQuotes($tmp_row['num_matches']);
			}
			mysql_free_result($tmp_result);	
			
			$query .= (',' . sqlSafeStringQuotes($site->linebreaks(utf8_encode($row['comment'])))
					   . ',' . sqlSafeStringQuotes($site->linebreaks(utf8_encode($row['comment'])))
					   . ',' . sqlSafeStringQuotes($row['logo']) . ',' . sqlSafeStringQuotes($row['created'])
					   . ')');
			// execute query, ignore result
			@$site->execute_query('teams_overview', $query, $connection);
		}
		mysql_free_result($result);
	}
	
	
	// matches
	function import_matches()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `bzl_match` ORDER BY `id`';
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
			if (strcmp($row['idedit'],'') === 0)
			{
				// was player deleted?
				if (isset($deleted_players[$row['identer']]))
				{
					// grab id from database to ensure no foreign key constraint gets violated
					$query .= ',' . sqlSafeStringQuotes($deleted_players[$row['identer']]['id']);
				} else
				{
					$query .= ',' . sqlSafeStringQuotes($row['identer']);
				}
			} else
			{
				// was player deleted?
				if (isset($deleted_players[$row['idedit']]))
				{
					// grab id from database to ensure no foreign key constraint gets violated
					$query .= ',' . sqlSafeStringQuotes($deleted_players[$row['idedit']]['id']);
				} else
				{
					$query .= ',' . sqlSafeStringQuotes($row['idedit']);
				}
			}
			if (strcmp($row['tsedit'],'') === 0)
			{
				$query .= ',' . sqlSafeStringQuotes($row['tsenter']);
			} else
			{
				$query .= ',' . sqlSafeStringQuotes($row['tsedit']);
			}
			$query .= (',' . sqlSafeStringQuotes($row['team1']) . ',' . sqlSafeStringQuotes($row['team2'])
					   . ',' . sqlSafeStringQuotes($row['score1']) . ',' . sqlSafeStringQuotes($row['score2'])
					   . ',' . sqlSafeStringQuotes($row['newrankt1']) . ',' . sqlSafeStringQuotes($row['newrankt2'])
					   . ',' . sqlSafeStringQuotes($row['mlength']) . ')');
			// execute query, ignore result
			@$site->execute_query('matches', $query, $connection);
		}
		mysql_free_result($result);
	}
	
	
	// private messages
	function import_mails()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `l_message` ORDER BY `datesent`';
		if (!($result = @$site->execute_query('l_team', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		while ($row = mysql_fetch_array($result))
		{
			$query = ('INSERT INTO `messages_storage` (`id`,`author_id`,`subject`,`timestamp`,`message`,`from_team`,`recipients`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['msgid']));
			if ((int) $row['fromid'] > 0)
			{
				$query .=  ',' . sqlSafeStringQuotes($deleted_players[$row['fromid']]['id']);
			} else
			{
				$query .= ',0';
			}
			if (strcmp($row['subject'], '') === 0)
			{
				$query .= ',' . sqlSafeStringQuotes('Enter subject here');
			} else
			{
				$query .= ',' . sqlSafeStringQuotes(utf8_encode(htmlentities($row['subject'], ENT_QUOTES, 'ISO-8859-1')));
			}
			$query .= ',' . sqlSafeStringQuotes($row['datesent']);
			
			$msg = utf8_encode($row['msg']);
			if (strcmp($row['htmlok'], '1') === 0)
			{
				$msg = str_replace('&nbsp;', ' ', $msg);
				$msg = str_replace('<br>', "\n", $msg);
				$msg = str_replace('<BR>', "\n", $msg);
				$query .= ',' . sqlSafeStringQuotes(strip_tags($msg));
			} else
			{
				$query .= ',' . sqlSafeStringQuotes($msg);
			}
			//		if (strcmp($row['team'], 'no') === 0)
			//		{
			//			$query .= ',' . sqlSafeStringQuotes('0');
			//		} else
			//		{
			//			$query .= ',' . sqlSafeStringQuotes('1');
			//		}
			// the messages are sent multiple times in practice
			$query .= ',' . sqlSafeStringQuotes('0');
			$query .= ',' . sqlSafeStringQuotes($deleted_players[$row['toid']]['id']) . ')';
			// execute query, ignore result
			@$site->execute_query('messages_storage', $query, $connection);
			
			$query = ('INSERT INTO `messages_users_connection` (`msgid`,`playerid`,`in_inbox`,`in_outbox`,`msg_status`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['msgid'])
					  . ',' . sqlSafeStringQuotes($deleted_players[$row['toid']]['id'])
					  // all messages only in inbox
					  . ',' . sqlSafeStringQuotes('1')
					  . ',' . sqlSafeStringQuotes('0')
					  // copy status from old database
					  . ',' . sqlSafeStringQuotes($row['status'])
					  . ')');
			
			// execute query, ignore result
			@$site->execute_query('messages_users_connection', $query, $connection);
		}
	}
	
	
	// visits log
	function import_visits_log()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `bzl_visit` ORDER BY `ts`';
		if (!($result = @$site->execute_query('l_team', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		// use lookup array to save some dns lookups
		while ($row = mysql_fetch_array($result))
		{
			// webleague can have funny entries with pid being 0!
			if ((int) $row['pid'] > 0)
			{
				$query = ('INSERT INTO `visits` (`playerid`,`ip-address`,`host`,`timestamp`)'
						  . ' VALUES '
						  . '(' . sqlSafeStringQuotes($deleted_players[$row['pid']]['id'])
						  . ',' . sqlSafeStringQuotes($row['ip'])
						  // set host to empty and update it in the background afterwards
						  . ',' . sqlSafeStringQuotes('')
						  . ',' . sqlSafeStringQuotes($row['ts'])
						  . ')');
				// execute query, ignore result
				@$site->execute_query('visits', $query, $connection);
			}
		}
	}
	
	
	// news entries
	function import_news()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `bzl_news` ORDER BY `newsdate`';
		if (!($result = @$site->execute_query('l_team', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		while ($row = mysql_fetch_array($result))
		{
			$text = $site->linebreaks($row['text']);
			$text = str_replace('&', '&amp;', $text);
			
			$query = ('INSERT INTO `news` (`timestamp`,`author`,`announcement`,`raw_announcement`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['newsdate'])
					  . ',' . sqlSafeStringQuotes($row['authorname'])
					  . ',' . sqlSafeStringQuotes($text)
					  . ',' . sqlSafeStringQuotes($text)
					  . ')');
			// execute query, ignore result
			@$site->execute_query('news', $query, $connection);
		}
	}
	
	
	// ban entries
	function import_bans()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `bzl_shame` ORDER BY `newsdate`';
		if (!($result = @$site->execute_query('l_team', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		while ($row = mysql_fetch_array($result))
		{
			$query = ('INSERT INTO `bans` (`timestamp`,`author`,`announcement`,`raw_announcement`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['newsdate'])
					  . ',' . sqlSafeStringQuotes($row['authorname'])
					  . ',' . sqlSafeStringQuotes($site->linebreaks($row['text']))
					  . ',' . sqlSafeStringQuotes($site->linebreaks($row['text']))
					  . ')');
			// execute query, ignore result
			@$site->execute_query('bans', $query, $connection);
		}
	}
	
		// ban entries
	function import_seasons()
	{
		global $site;
		global $connection;
		global $deleted_players;
		global $db_to_be_imported;
		
		$site->selectDB($db_to_be_imported, $connection);
		$query = 'SELECT * FROM `l_season` ORDER BY `startdate`';
		if (!($result = @$site->execute_query('l_season', $query, $connection)))
		{
			$site->selectDB($site->db_used_name(), $connection);
			$site->dieAndEndPage('');
		}
		$site->selectDB($site->db_used_name(), $connection);
		while ($row = mysql_fetch_array($result))
		{
			$query = ('INSERT INTO `seasons` (`startdate`,`enddate`,`points_win`,`points_draw`, `points_lost`, `active`)'
					  . ' VALUES '
					  . '(' . sqlSafeStringQuotes($row['startdate'])
					  . ',' . sqlSafeStringQuotes($row['enddate'])
					  . ',' . sqlSafeStringQuotes($row['points_win'])
					  . ',' . sqlSafeStringQuotes($row['points_draw'])
					  . ',' . sqlSafeStringQuotes($row['points_lost'])
					  . ',' . sqlSafeStringQuotes('yes') . ')' );
			// execute query, ignore result
			@$site->execute_query('seasons', $query, $connection);
		}
		recalculate_all_seasons();
	}
	
	
	function recalculate_all_seasons() 
	{
		global $connection;
		global $site;
		
		//going through seasons
		$site->selectDB($site->db_used_name(), $connection);
	
		$query = 'SELECT * FROM `seasons` WHERE startdate != enddate ORDER BY `startdate`';
		if (!($result_seasons = @$site->execute_query('seasons', $query, $connection)))
		{
			$site->dieAndEndPage('');
		}
		while ($row_seasons = mysql_fetch_array($result_seasons))
		{
			$seasonid = $row_seasons['id'];
			
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
		
			if (mysql_num_rows($result) > 0 )
			{
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
							$site->dieAndEndPage('The seasons best teams could not be updated due to a sql problem!');
						}
					$place_no++;
				}
			}
		}
	}
	
	
	
	
	
	// visits log host entry completer
	function resolve_visits_log_hosts()
	{
		global $site;
		global $connection;
		
		//we put that date cause old data are not so useful and whole import takes very long here. 
		$import_start_date = '2010-11-01';
		
		// get a rough max estimate of entries to be updated
		$query = 'SELECT COUNT(*) AS `n` FROM `visits` WHERE `host`=' . sqlSafeStringQuotes('') 
		. ' AND `ip-address` <> ' . sqlSafeStringQuotes('NULL')
		
		. ' AND timestamp > ' .	sqlSafeStringQuotes($import_start_date);
		if (!($result = @$site->execute_query('visits', $query, $connection)))
		{
			$site->dieAndEndPage('Could not get list of entries where hostname is empty');
		}
		$num_results = mysql_num_rows($result);
		$n = 0;
		while ($row = mysql_fetch_array($result))
		{
			$n = intval($row['n']);
		}
		mysql_free_result($result);
		for ($i = 1; $i <= $n; $i++)
		{
			// select only 1 entry
			$query = 'SELECT `ip-address` FROM `visits` WHERE `host`=' . sqlSafeStringQuotes('') 
			. ' AND `ip-address` <> ' . sqlSafeStringQuotes('NULL') 
			//we put that date cause old data are not so useful 
			. ' AND timestamp > ' .	sqlSafeStringQuotes($import_start_date) 
			. ' LIMIT 1';
			if (!($result = @$site->execute_query('visits', $query, $connection)))
			{
				$site->dieAndEndPage('Could not get list of entries where hostname is empty');
			}
			$num_results = mysql_num_rows($result);
			if ($num_results < 1)
			{
				// if no entry was found all host names have been resolved
				// so abort the function at this point
				mysql_free_result($result);
				return;
			}
			$ip_address = '';
			while ($row = mysql_fetch_array($result))
			{
				$ip_address = $row['ip-address'];
			}
			mysql_free_result($result);
			resolve_visits_log_hosts_helper($ip_address);
			unset($ip_address);
		}
	}
	function resolve_visits_log_hosts_helper($ip_address)
	{
		global $site;
		global $connection;
		
		$query = ('UPDATE `visits` SET `host`='
				  . sqlSafeStringQuotes(gethostbyaddr($ip_address))
				  . ' WHERE `ip-address`=' . sqlSafeStringQuotes($ip_address));
		// execute query, ignore result
		@$site->execute_query('visits', $query, $connection);
	}
	
	// create lookup array
	$deleted_players = array(array());
	// DUMMY player
	$deleted_players['0']['callsign'] = 'CTF League System';
	$deleted_players['0']['dummy'] = true;
	
	//this should be set with initial import on clear database;
	clear_current_tables();
	
	reset_auto_increment();
	import_players();
	import_teams();
	import_matches();
	import_mails();
	import_visits_log();
	import_news();
	import_bans(); 
	import_seasons(); 
	
	// lookup array no longer needed
	unset($deleted_players);
	
	// do maintenance after importing the database to clean it
	// a check inside the maintenance logic will make sure it will be only performed one time per day at max
	require_once('CMS/maintenance/index.php');
	
	// (should take about 3 minutes to import the data until this point)
	// disable this when not doing the final import because this last step would take 90 minutes
	resolve_visits_log_hosts();
	
	// done
?>
<p>Import finished!</p>
</div>
</body>
</html>