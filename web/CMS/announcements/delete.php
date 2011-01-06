<?php
	// this file handles deleting entries from table $table_name of database
	
	// check again for delete entry permission, just in case
	if ($_SESSION[$entry_delete_permission])
	{
		
		echo '<div class="main-box msg-box">';
		if (isset($_GET['delete']))
		{
			// cast the entry in order to close possible security holes
			$currentId = (int) ($_GET['delete']);
			// starting index in databse is 1
			if ($currentId < 1)
			{
				$currentId = 1;
			}
		}
		
		// the variable $currentId could now be used savely
		// use a form with a random generated key to edit data in order to
		// prevent third party websites from editing entries by links
		
		// make sure the magic key matches
		$randomkeysmatch = $site->compare_keys($randomkey_name);
		
		// $previewSeen == 1 means we're about to delete the data
		if (($previewSeen == 1) && $randomkeysmatch)
		{
			if ($message_mode)
			{
				// the request string contains the playerid, which takes care of permissions
				$user_id = 0;
				$box_name = sqlSafeString('in_' . $folder);
				$user_id = sqlSafeString(getUserID());
				
				// delete the message in the user's folder by his own request
				// example query: DELETE FROM `messages_users_connection` WHERE `playerid`='1194' `msgid`='66' AND `in_inbox`='1'
				$query = 'DELETE FROM `messages_users_connection` WHERE `playerid`=' . "'" . ($user_id) . "'";
				$query .= ' AND `msgid`=' . sqlSafeStringQuotes($currentId) . ' AND `' . $box_name . '`=' . "'" . 1 . "'";
				$result = $site->execute_query('messages_users_connection', $query, $connection);
				if ($result)
				{
					// give feedback, the user might want to know deleting was successfull so far
					echo '<p>The chosen message was deleted from your ' . htmlentities($folder) . '.</p>';
				}
				
				// IMPORTANT: Do the query _after_ the deletion of the message entry or resubmitting the form will break the script!
				// get the list of messages, we need to know if more than one user stores the message
				// example query: SELECT `id` FROM `messages_users_connection` WHERE `msgid`='66' LIMIT 0,1
				$message_is_stored_several_times = true;
				$query = 'SELECT `id` FROM `messages_users_connection` WHERE `msgid`=' . sqlSafeStringQuotes($currentId) . ' LIMIT 0,1';
				$result = $site->execute_query($table_name, $query, $connection);
				if ($result)
				{
					$rows = (int) mysql_num_rows($result);
					if ($rows < 1)
					{
						$message_is_stored_several_times = false;
					}
				}
				
				// if the message was only saved by one user we can actually delete the message itself now
				if (!$message_is_stored_several_times)
				{
					if ($site->debug_sql())
					{
						echo '<p>This message was owned by only one player. Deleting the actual message now.</p>';
					}
					// example query: DELETE FROM `messages_storage` WHERE `id`='11'
					$query = 'DELETE FROM `messages_storage` WHERE `id`=' . sqlSafeStringQuotes($currentId);
					$result = $site->execute_query('messages_storage', $query, $connection);
					if ($result)
					{
						if ($site->debug_sql())
						{
							// give feedback, the user might want to know deleting was entirely successful
							echo '<p>The actual message was deleted successfully!</p>';
						}
					}
				}
			} else
			{
				$query = 'DELETE FROM ' . $table_name .' WHERE id=' . sqlSafeString($currentId);
				$result = $site->execute_query($table_name, $query, $connection);
				if ($result)
				{
					echo '<p>Deleting: No problems occured, entry deleted!</p>' . "\n";
					$previewSeen=0;
				} else
				{
					echo '<p>Seems like deletion failed.<ü>';
				}
			}
		} else
		{
			if ($message_mode)
			{
				echo '<form class="deletion_preview" action="' . baseaddress() . $site->base_name() . '/?delete=' . sqlSafeString($currentId) . '&amp;folder=';
				echo $folder . '" method="post">' . "\n";
				echo '<p>Are you sure to delete the following message? ';
				$site->write_self_closing_tag('input type="submit" value="Delete message" class="button"');
			} else
			{
				echo '<form class="deletion_preview" action="' . baseaddress() . $name . '/?delete=' . $currentId . '" method="post">' . "\n";
				echo '<p>Are you sure to delete the following entry? ';
				$site->write_self_closing_tag('input type="submit" value="Delete entry" class="button"');
			}
			echo '</p>' . "\n";
			
			$site->write_self_closing_tag('input type="hidden" name="preview" value="' . '1' . '"');
	
			// random key
			$site->write_self_closing_tag('input type="hidden" name="' . $randomkey_name . '" value="'
										  . urlencode(($_SESSION[$randomkey_name])) . '"');
			
			echo '</form>' . "\n";
			if ($message_mode)
			{
				require_once('msgUtils.php');
				$query = ('SELECT `subject`'
						  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `name` FROM `players` WHERE `id`=`author_id`)'
						  . ',' . sqlSafeStringQuotes($site->displayed_system_username()) . ') AS `author`'
						  . ',IF(`messages_storage`.`author_id`<>0,(SELECT `status` FROM `players` WHERE `id`=`author_id`),'
						  . sqlSafeStringQuotes('') . ') AS `author_status`'
						  . ',`author_id`,`timestamp`,`message`,`messages_storage`.`from_team`,`messages_storage`.`recipients`'
						  . ' FROM `messages_storage`,`messages_users_connection`'
						  . ' WHERE `messages_storage`.`id`=`messages_users_connection`.`msgid`'
						  . ' AND `messages_users_connection`.`playerid`=' . sqlSafeStringQuotes(getUserID())
						  . ' AND `messages_storage`.`id`=' . sqlSafeStringQuotes($currentId)
						  // we need only 1 entry to know if the message does not exist
						  // or if there are no permissions to view the message
						  // but we do not know which one of both is exactly not fulfilled
						  . ' LIMIT 1');
				$result = ($site->execute_query($table_name, $query, $connection));
				if (!$result)
				{
					$site->dieAndEndPage();
				}
				displayMessage($result, $reply_possible, $connection, sqlSafeString($currentId));
				unset($reply_possible);
				$site->dieAndEndPage();
			}
			
			// the "LIMIT 0,1" part of query means only the first entry is received
			// this speeds up the query as there is only one row as result anyway
			$query = 'SELECT * FROM `' . $table_name . '` WHERE `id`=' . sqlSafeStringQuotes($currentId) . ' LIMIT 0,1';
			$result = ($site->execute_query($table_name, $query, $connection));
			if (!$result)
			{
				$site->dieAndEndPage();
			}
			
			$rows = mysql_num_rows($result);
			// there is only one row as result
			if ($rows === 1)
			{
				// read each entry, row by row
				while($row = mysql_fetch_array($result))
				{
					// display the row to the user
					echo '<div class="article">' . "\n";
					echo '<div class="article_header">' . "\n";
					echo '<div class="timestamp">';
					echo htmlent($row['timestamp']);
					echo '</div>' . "\n";
					echo '<div class="author"> By: ';
					echo htmlent($row['author']);
					echo '</div>' . "\n";
					echo '</div>' . "\n";
					echo '<p>' . $row['announcement'] . '</p>' . "\n";
					echo '</div>' . "\n\n";
				}
				// done
				mysql_free_result($result);
			}
		}
		echo '</div>';
	}
?>