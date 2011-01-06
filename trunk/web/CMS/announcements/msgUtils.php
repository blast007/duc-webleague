<?php	
	function formatOverviewText($text, $id, $folder, $unread)
	{
		if ($unread)
		{
			return '<a class="msg_overview_unread" href="./?folder=' . urlencode($folder) . '&amp;view=' . htmlentities((int) $id) . '">' . ($text) . '</a>';
		} else
		{
			return '<a href="./?folder=' . urlencode($folder) . '&amp;view=' . htmlentities((int) $id) . '">' . ($text) . '</a>';
		}
	}
	
	function find_recipient_player(&$item, $key, $connection)
	{
		// $item is passed by reference and therefore changes to $item will be passed back to the caller function
		
		// find out player name by the specified player id in the variable named $item
		$query = 'SELECT `name` FROM `players` WHERE `id`=' . sqlSafeStringQuotes($item) . ' LIMIT 1';
		$result = @mysql_query($query, $connection);
		
		// initialise the variable with an error message
		// if the query is successfull the variable will be overwritten with the player name
		$result_callsign = 'Unknown player!';
		
		// read each entry, row by row
		while ($row = mysql_fetch_array($result))
		{
			$result_callsign = '<a href="../Players/?profile=' . htmlspecialchars($item) . '">' .$row['name'] . '</a>';
		}
		mysql_free_result($result);
		
		// pass the result to $item which is used by reference
		$item = $result_callsign;
	}
	
	function find_recipient_team(&$item, $key, $connection)
	{
		// $item is passed by reference and therefore changes to $item will be passed back to the caller function
		
		// find out team name by the specified team id in the variable named $item		  
		$query = 'SELECT `name` FROM `teams` WHERE `id`=' . sqlSafeStringQuotes($item) . ' LIMIT 1';
		$result = @mysql_query($query, $connection);
		
		// initialise the variable with an error message
		// if the query is successfull the variable will be overwritten with the player name
		$result_team = 'Unknown team!';
		
		// read each entry, row by row
		while($row = mysql_fetch_array($result))
		{
			$result_team = '<a href="../Teams/?profile=' . htmlspecialchars($item) . '">' .$row['name'] . '</a>';
		}
		mysql_free_result($result);
		
		// pass the result to $item which is used by reference
		$item = $result_team;
	}
	
	function displayMessage(&$result, &$can_reply, &$team_message_from_team_id, $id)
	{
		global $site;
		global $connection;
		global $folder;
		
		$can_reply = false;
		
		// display a single message (either in inbox or outbox) in all its glory
		while($row = mysql_fetch_array($result))
		{
			// find out the sending team
			$team_message_from_team_id = intval($row['from_team']);
			// from_team is 1 if it is a team message, 0 otherwise
			if ($team_message_from_team_id > 0)
			{
				$team_message_from_team_id = intval($row['recipients']);
			}
				
			echo '<div class="msg_area">' . "\n";
			echo '<div class="msg_view_full">' . "\n";
			
			echo '	<div class="msg_header_full">' . "\n";
			echo '		<span class="msg_subject">' .  $row['subject'] . '</span>' . "\n";
			echo '		<span class="msg_author"> by ' .  htmlent($row['author']) . '</span>' . "\n";
			if (strlen($row['author_status']))
			{
				if (strcmp($row['author_status'], 'deleted') === 0)
				{
					echo ' (deleted)';
				} elseif (strcmp($row['author_status'], 'active') !== 0)
				{
					echo ' (banned)';
				}
			}
			echo '		<span class="msg_timestamp"> at ' .	 htmlent($row['timestamp']) . '</span>' . "\n";
			echo '	</div>' . "\n";
			// adding to string using . will put the message first, then the div tag..which is wrong
			echo '	<div class="msg_contents">';
			echo $site->bbcode($row['message']);
			echo '</div>' . "\n";
			echo '</div>' . "\n";
			echo '</div>' . "\n\n";
			
			if (strcmp($row['author_status'], 'active') === 0)
			{
				$can_reply = true;
			}
		}
		
		// folder is NULL in case a message is either being deleted or being sent
		if (!($folder === NULL) && (!(strcmp($folder, 'outbox') === 0)))
		{
			// mark the message as read for the current user
			$query = ('UPDATE LOW_PRIORITY `messages_users_connection`'
					  . 'SET `msg_status`=' . sqlSafeStringQuotes('read')
					  . ' WHERE `msgid`=' . sqlSafeStringQuotes($id)
					  . ' AND `in_' . $folder . '`=' . sqlSafeStringQuotes('1')
					  . ' AND `playerid`=' . sqlSafeStringQuotes(getUserID())
					  . ' LIMIT 1');
			// TODO: find out why connection isn't set up proper
			// TODO: when this function is called from final deletion step in delete.php
			$connection = $site->connect_to_db();
			// silently ignore the result, it's not a resource anyway so it can't even be dropped
			$site->execute_query('messages_users_connection', $query, $connection);
		}
	}
	
	// read the array using helper function
	function displayMessageSummary(&$result)
	{
		global $site;
		global $connection;
		global $folder;
		global $user_id;
		
		$box_name = sqlSafeString('in_' . $folder);
		
		// read each entry, row by row
		while ($row = mysql_fetch_array($result))
		{
			// FIXME: display possibility to delete messages in the summary (checkboxes in each row and one button at the end of the list)
			
			// TODO: implement delete button in each row
			// TODO: implement class in stylesheets
			echo '<tr class="msg_overview">' . "\n";
			$currentId = $row['msgid'];
			// set $unread to true if status is new
			$unread = strcmp($row['msg_status'], 'new') === 0;
			// TODO: implement class in stylesheets
			echo '	<td class="msg_overview_author">';
			// player id 0 is reserved
			if ((int) $row['author_id'] > 0)
			{
				echo '<a href="../Players/?profile=' . htmlspecialchars($row['author_id']) . '">';
			}
			echo $row['author'];
			if (strlen($row['author_status']) > 0)
			{
				if (strcmp($row['author_status'], 'deleted') === 0)
				{
					echo ' (deleted)';
				} elseif (!strcmp($row['author_status'], 'active') === 0)
				{
					echo ' (banned)';
				}
			}
			if ((int) $row['author_id'] > 0)
			{
				echo '</a>' . "\n";
			}
			echo '</td>';
			// TODO: implement class in stylesheets
			echo '	<td class="msg_overview_subject">' . (formatOverviewText($row["subject"], $currentId, $folder, $unread)) . '</td>' . "\n";
			// TODO: implement class in stylesheets
			echo '	<td class="msg_overview_timestamp">' . $row["timestamp"] . '</td>' . "\n";
			
			echo '	<td class="msg_overview_recipients">';
			
			// save the recipients as an array
			$recipients = explode(' ', $row['recipients']);
			
			//print_r($recipients);
			// TODO: implement class msg_overview_recipients in stylesheets
			if (strcmp('0', $row['from_team']) == 0)
			{
				// message has one or more players as recipient(s)
				array_walk($recipients, 'find_recipient_player', $connection);
			} else
			{
				// message has one or more teams as recipient(s)
				array_walk($recipients, 'find_recipient_team', $connection);
			}
			echo (implode('<span class="msg_recipient_seperator">, </span>', $recipients));
			
			echo '</td>' . "\n";
			echo '</tr>' . "\n\n";
			
		}
		// query results are no longer needed
		mysql_free_result($result);
	}
	
	class folderDisplay
	{
		// display a message folder well formatted
		function displayMessageFolder($folder, $connection, $site)
		{
			// default folder is inbox
			if (strcmp($folder, '') === 0)
			{
				$folder = 'inbox';
			}
			
			// put some values into variables to make query more generic
			$box_name = sqlSafeString('in_' . $folder);
			$user_id = 0;
			
			if (getUserID() > 0)
			{
				$user_id = sqlSafeString(getUserID());
			}
			
			if (isset($_GET['view']))
			{
				// sanity checks needed before displaying
				// make sure the id is an int
				$id = (int) sqlSafeString($_GET['view']);
				
				// display a certain message
				// make sure the one who wants to read the message has actually permission to read it
				$query = ('SELECT `subject`'
						  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `name` FROM `players` WHERE `id`=`author_id`)'
						  . ',' . sqlSafeStringQuotes($site->displayed_system_username()) . ') AS `author`'
						  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `status` FROM `players` WHERE `id`=`author_id`),'
						  . sqlSafeStringQuotes('') . ') AS `author_status`'
						  . ',`author_id`,`timestamp`,`message`,`messages_storage`.`from_team`,`messages_storage`.`recipients`'
						  . ' FROM `messages_storage`,`messages_users_connection`'
						  . ' WHERE `messages_storage`.`id`=`messages_users_connection`.`msgid`'
						  . ' AND `messages_users_connection`.`playerid`=' . sqlSafeStringQuotes($user_id)
						  . ' AND `messages_users_connection`.`' . sqlSafeString($box_name)  .'`=' . sqlSafeStringQuotes('1')
						  . ' AND `messages_storage`.`id`=' . sqlSafeStringQuotes($id)
						  // we need only 1 entry to know if the message does not exist
						  // or if there are no permissions to view the message
						  // but we do not know which one of both is exactly not fulfilled
						  . ' LIMIT 1');
				
				$result = $site->execute_query('messages_users_connection', $query, $connection);
				$rows = (int) mysql_num_rows($result);
				if ($rows === 1)
				{
					
					// message came from a team?
					$team_message_from_team_id = false;
					// display the message chosen by user
					displayMessage($result, $can_reply, $team_message_from_team_id, $id);
					
					echo '<div class="msg_view_button_list">' . "\n";
					// if the message is in inbox the user might want to reply to the message
					if (strcmp($folder, 'inbox') === 0)
					{
						if ($can_reply)
						{
							if ($team_message_from_team_id > 0)
							{
								// the message actually came from a team
								echo '<form class="msg_buttons" action="' . baseaddress() . $site->base_name() . '/?add&amp;reply=team&amp;id=' . htmlent($id);
								echo '&amp;teamid=' . urlencode($team_message_from_team_id) . '" method="post">' . "\n";
								echo '<p>';
								$site->write_self_closing_tag('input type="submit" value="Reply to team"');
								echo '</p>' . "\n";
								echo '</form>' . "\n";
							}
							echo '<form class="msg_buttons" action="' . baseaddress() . $site->base_name() . '/?add&amp;reply=players&amp;id=' . htmlent($id);
							echo '" method="post">' . "\n";
							echo '<p>';
							$site->write_self_closing_tag('input type="submit" value="Reply to player(s)"');
							echo '</p>' . "\n";
							echo '</form>' . "\n";
						}
					}
					// query result no longer needed
					mysql_free_result($result);
					
					// the user might want to delete the message
					echo '<form class="msg_buttons" action="' . baseaddress() . $site->base_name() . '/?delete=' . ((int) $id) . '&amp;folder=';
					echo $folder . '" method="post">' . "\n";
					echo '<p>';
					$site->write_self_closing_tag('input type="submit" value="Delete this message"');
					echo '</p>' . "\n";
					echo '</form>' . "\n";
					
				
					
					echo '</div>' . "\n";
				} else
				{
					echo '<p>You have either no permission to view the message or the message does not exist</p>';
				}
				
			} else
			{
				// show the overview
				$query = ('SELECT `messages_users_connection`.`msgid`'
						  . ',`messages_storage`.`author_id`'
						  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `name` FROM `players` WHERE `id`=`author_id`)'
						  . ',' . sqlSafeStringQuotes($site->displayed_system_username()) . ') AS `author`'
						  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `status` FROM `players` WHERE `id`=`author_id`),'
						  . sqlSafeStringQuotes('') . ') AS `author_status`'
						  . ',`messages_users_connection`.`msg_status`'
						  . ',`messages_storage`.`subject`'
						  . ',`messages_storage`.`timestamp`'
						  . ',`messages_storage`.`from_team`'
						  . ',`messages_storage`.`recipients`'
						  . ' FROM `messages_users_connection`,`messages_storage`'
						  . ' WHERE `messages_storage`.`id`=`messages_users_connection`.`msgid`'
						  . ' AND `messages_users_connection`.`playerid`=' . sqlSafeStringQuotes($user_id)
						  . ' AND `' . $box_name . '`=' . sqlSafeStringQuotes('1')
						  . ' ORDER BY `messages_users_connection`.`id` ');
				// newest messages first please
				$query .= 'DESC ';
				// limit the output to the requested rows to speed up displaying
				$query .= 'LIMIT ';
				// the "LIMIT 0,200" part of query means only the first 200 entries are received
				// the range of shown messages is set by the GET variable i
				$view_range = (int) 0;
				if (isset($_GET['i']))
				{
					if (((int) $_GET['i']) > 0)
					{
						$view_range = (int) $_GET['i'];
						$query .=  $view_range . ',';
					} else
					{
						// force write 0 for value 0 (speed)
						// and 0 for negative values (security: DBMS error handling prevention)
						$query .= '0,';
					}
				} else
				{
					// no special value set -> write 0 for value 0 (speed)
					$query .= '0,';
				}
				$query .= 201;
				
				$result = $site->execute_query('messages_users_connection', $query, $connection);
				
				$rows = (int) mysql_num_rows($result);
				$show_next_messages_button = false;
				if ($rows > 200)
				{
					$show_next_messages_button = true;
				}
				if ($rows < 1)
				{
					echo '<div class="msg_overview">No messages in ' . $folder . '.</div>';
					mysql_free_result($result);
				} else
				{
					// table of messages
					echo "\n" . '<table id="table_msg_overview" class="big">' . "\n";
					echo '<caption>Messages in ' . $folder . '</caption>' . "\n";
					echo '<tr>' . "\n";
					echo '	<th>Author</th>' . "\n";
					echo '	<th>Subject</th>' . "\n";
					echo '	<th>Date</th>' . "\n";
					echo '	<th>Recipient(s)</th>' . "\n";
					echo '</tr>' . "\n\n";
					
					// display message overview
					displayMessageSummary($result);
					
					echo '</table>' . "\n";
					// look up if next and previous buttons are needed to look at all messages in overview
					if ($show_next_messages_button || ($view_range !== (int) 0))
					{
						// browse previous and next entries, if possible
						echo "\n" . '<p>'  . "\n";
						
						if ($view_range !== (int) 0)
						{
							echo '	<a href="./?folder=';
							echo $folder;
							echo '&amp;i=';
							echo ((int) $view_range)-200;
							echo '">Previous messages</a>' . "\n";
						}
						if ($show_next_messages_button)
						{
							
							echo '	<a href="./?folder=';
							echo $folder;
							echo '&amp;i=';
							echo ((int) $view_range)+200;
							echo '">Next messages</a>' . "\n";
						}
						echo '</p>' . "\n";
					}
				}
			}
		}
	}
?>