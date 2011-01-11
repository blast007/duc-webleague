<?php
	// this file handles deleting entries from table $table_name of database
	
	// check again for delete entry permission, just in case
	if ($_SESSION[$entry_delete_permission])
	{
		
		echo '<div class="main-box msg-box">';
		//more than one message
		if (isset($_POST['delete_all'])) 
		{
			if (!isset($_POST['delete']) || empty($_POST['delete']))
			{
				echo '<p> There are no messages to delete</p>';
				$site->dieAndEndPage();
				
			} else
			{
				// make sure the magic key matches
				$randomkeysmatch = $site->compare_keys($randomkey_name);
				
				// $previewSeen == 1 means we're about to delete the data
				if (($previewSeen == 1) && $randomkeysmatch)
				{
				
					foreach($_POST['delete'] as $postvar) 
					{
						$msgId = (int) $postvar;
						
						deleteMessage($msgId, false);
					}
					echo '<p>The chosen messages were deleted from your ' . htmlentities($folder) . '.</p>';
					
					
				} else
				{
					echo '<form class="deletion_preview" action="' . baseaddress() . $site->base_name() . '/?folder=';
					echo $folder . '" method="post">' . "\n";
					echo '<p>Are you sure to delete the following messages? ';
			
					$site->write_self_closing_tag('input type="submit" name="delete_all" value="Delete messages" class="button"');
					$site->write_self_closing_tag('input type="hidden" name="preview" value="' . '1' . '"');
			
					// random key
					$site->write_self_closing_tag('input type="hidden" name="' . $randomkey_name . '" value="'
												  . urlencode(($_SESSION[$randomkey_name])) . '"');
								
					echo '</p>';
					
					$user_id = 0;
					$box_name = sqlSafeString('in_' . $folder);		
					$user_id = sqlSafeString(getUserID());
					
					$msgsId = '-1';
					foreach($_POST['delete'] as $postvar) 
					{
						$msgId = (int) $postvar;
						
						$msgsId .= ',' . $msgId;
					}
					
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
							  . ' AND `messages_users_connection`.`msgid` IN (' . $msgsId .')'
							  . ' ORDER BY `messages_users_connection`.`id` ');
					// newest messages first please
					$query .= 'DESC ';
					
					$result = $site->execute_query('messages_users_connection', $query, $connection);
					// table of messages
					echo "\n" . '<table id="table_msg_overview" class="big">' . "\n";
					echo '<tr>' . "\n";
					echo '	<th>Author</th>' . "\n";
					echo '	<th>Subject</th>' . "\n";
					echo '	<th>Date</th>' . "\n";
					echo '	<th>Recipient(s)</th>' . "\n";
					echo '</tr>' . "\n\n";
					
					// display message overview
					displayMessageSummary($result, 'deleting');
					
					echo '</table>' . "\n";
					echo '</form>';
					$site->dieAndEndPage();
				}
			}
		} else
		{
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
					deleteMessage($currentId);
					
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
		}
		echo '</div>';
	}
?>