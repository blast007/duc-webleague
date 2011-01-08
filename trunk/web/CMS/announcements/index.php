<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	$path = (pathinfo(realpath('./')));
	$display_page_title = $path['basename'];
	$page_title = $display_page_title;
	
	require_once (dirname(dirname(__FILE__)) . '/siteinfo.php');
	$site = new siteinfo();
	
	
	if (strcmp($page_title, '') === 0)
	{
		echo '<div class="static_page_box">' . "\n";
		$site->dieAndEndPage('Error: No page title specified!');;
	}
	
	if (isset($_GET['edit']))
	{
		$display_page_title = 'Page content editor: ' . $page_title;
	}
	require_once (dirname(dirname(__FILE__)) . '/index.inc');
	
	
	// next line also sets $connection = $site->connect_to_db();
	require (dirname(dirname(__FILE__)) . '/navi.inc');
	
	// check if user did login
	$logged_in = false;
	if (isset($_SESSION['user_logged_in']))
	{
		$logged_in = $_SESSION['user_logged_in'];
	}
	
	if (!(isset($message_mode)))
	{
		$message_mode = false;
	}
	
	if (!(isset($allow_different_timestamp)))
	{
		$allow_different_timestamp = false;
	}
	
	echo '<h1 class="news">' . $display_page_title . '</h1>';
	
	// only logged in users can read messages
	// usually the permission system should take care of permissions anyway
	// but just throw one more sanity check at it, for the sake of it
	// it also helps to print out a nice message to the user
	if ($message_mode && !$logged_in)
	{
		echo '<div class="static_page_box">' . "\n";
		echo '<p>You need to login in order to view your private messages.</p>' . "\n";
		die("\n</div>\n</body>\n</html>");
	}
	
	// any of the variables is set and the user is not logged in
	if (((isset($_GET['add'])) || (isset($_GET['edit'])) || isset($_GET['delete'])) && (!$logged_in))
	{
		echo '<div class="static_page_box">' . "\n";
		echo '<p>You need to login in order to change any content of the website.</p>' . "\n";
		die("\n</div>\n</body>\n</html>");
	}
	
	function db_create_when_needed($site, $connection, $message_mode, $table_name, $table_name_msg_user_connection)
	{
		// cache if we were successful
		$success = false;
		
		if ($message_mode)
		{
			//set up table structure for private messages when needed
			$query = 'SHOW TABLES LIKE ' . sqlSafeStringQuotes($table_name);
			$result = mysql_query($query, $connection);
			$rows = mysql_num_rows($result);
			// done
			mysql_free_result($result);
			
			if ($rows < 1)
			{
				echo '<p>Table does not exist. Attempting to create table.<p>';
				
				// query will be
				// CREATE TABLE `messages_storage` (
				//								 `id` int(11) unsigned NOT NULL auto_increment,
				//								 `timestamp` varchar(20) default NULL,
				//								 `author` varchar(255) default NULL,
				//								 `announcement` varchar(1000) default NULL,
				//								 `from_team` bit(1) default NULL,
				//								 PRIMARY KEY  (`id`)
				//								 ) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8
				$query = 'CREATE TABLE `' . $table_name . '` (' . "\n";
				$query = $query . '`id` int(11) unsigned NOT NULL auto_increment,' . "\n";
				$query = $query . '`timestamp` varchar(20) default NULL,' . "\n";
				$query = $query . '`author` varchar(255) default NULL,' . "\n";
				$query = $query . '`announcement` varchar(1000) default NULL,' . "\n";
				$query = $query . '`from_team` bit(1) default NULL,' . "\n";
				$query = $query . 'PRIMARY KEY	(`id`)' . "\n";
				$query = $query . ') ENGINE=MyISAM DEFAULT CHARSET=utf8';
				if (@$site->execute_query($table_name, $query, $connection))
				{
					$success = true;
				} else
				{
					echo "<p>Creation of table failed.</p>";
					die("\n</div>\n</body>\n</html>");
				}
			}
			
			// do the same for the table that connects the messages to its users
			$query = 'SHOW TABLES LIKE ' . sqlSafeStringQuotes($table_name_msg_user_connection);
			$result = mysql_query($query, $connection);
			$rows = mysql_num_rows($result);
			// done
			mysql_free_result($result);
			
			if ($rows < 1)
			{
				echo '<p>Table does not exist. Attempting to create table.<p>';
				
				// query will be
				// CREATE TABLE `messages_connect_users` (
				//									   `id` int(11) unsigned NOT NULL auto_increment,
				//									   `msgid` int(11) unsigned NOT NULL,
				//									   `playerid` int(11) unsigned NOT NULL,
				//									   `in_inbox` bit(1) NOT NULL,
				//									   `in_outbox` bit(1) NOT NULL,
				//									   PRIMARY KEY	(`id`)
				//									   ) ENGINE=MyISAM DEFAULT CHARSET=utf8 
				$query = 'CREATE TABLE `' . $table_name_msg_user_connection . '` (' . "\n";
				$query = $query . '`id` int(11) unsigned NOT NULL auto_increment,' . "\n";
				$query = $query . '`msgid` int(11) unsigned NOT NULL,' . "\n";
				$query = $query . '`playerid` int(11) unsigned NOT NULL,' . "\n";
				$query = $query . '`in_inbox` bit(1) NOT NULL,' . "\n";
				$query = $query . '`in_outbox` bit(1) NOT NULL,' . "\n";
				$query = $query . 'PRIMARY KEY	(`id`)' . "\n";
				$query = $query . ') ENGINE=MyISAM DEFAULT CHARSET=utf8';
				if ((@$site->execute_query($table_name, $query, $connection)))
				{
					$success = true;
				} else
				{
					echo "<p>Creation of table failed.</p>";
					die("\n</div>\n</body>\n</html>");
				}
			}
			// done setting up table structure for private messages
		} else
		{
			// set up table structure for announcements like news or bans when needed
			$query = 'SHOW TABLES LIKE ' . "'" . $table_name . "'";
			$result = mysql_query($query, $connection);
			$rows = mysql_num_rows($result);
			// done
			mysql_free_result($result);
			
			
			if ($rows < 1)
			{
				echo '<p>Table does not exist. Attempting to create table.<p>';
				
				// query will be
				//			CREATE TABLE `$table_name` (
				//										`id` int(11) unsigned NOT NULL auto_increment,
				//										`timestamp` varchar(20) default NULL,
				//										`author` varchar(255) default NULL,
				//										`announcement` text,
				//										PRIMARY KEY	 (`id`)
				//										) ENGINE=MyISAM DEFAULT CHARSET=utf8			
				$query = 'CREATE TABLE `' . $table_name . '` (' . "\n";
				$query = $query . '`id` int(11) unsigned NOT NULL auto_increment,' . "\n";
				$query = $query . '`timestamp` varchar(20) default NULL,' . "\n";
				$query = $query . '`author` varchar(255) default NULL,' . "\n";
				$query = $query . '`announcement` text,' . "\n";
				$query = $query . 'PRIMARY KEY	(`id`)' . "\n";
				$query = $query . ') ENGINE=MyISAM DEFAULT CHARSET=utf8';
				if ((@$site->execute_query($table_name, $query, $connection)))
				{
					$success = true;
				} else
				{
					echo "<p>Creation of table failed.</p>";
					die("\n</div>\n</body>\n</html>");
				}
			}
		}
		if ($success)
		{
			echo '<p>Updating: The missing table(s) were created successfully!</p>' . "\n";
			echo '<p class="simple-paging"><a class="button" href="./">overview</a><p>' . "\n";
		}
	}
	
	
	// random key to prevent third party sites from using links to post content
	// generate only one key in order to allow multiple tabs
	
	// the key is generated by setting variable $randomkey_name
	if (!(isset($_SESSION[$randomkey_name])))
	{
		// this should be good enough as all we need is something that can not be guessed without much tries
		$_SESSION[$randomkey_name] = $key = rand(0, getrandmax());
	}
	
	// cast to int to prevent possible security problems
	$previewSeen = 0;
	if (isset($_POST['preview']))
	{
		$previewSeen = (int) $_POST['preview'];
	}
	
	if (!$connection)
	{
		if ($site->debug_sql())
		{
			print mysql_error();
		}
		die("Connection to database failed.");
	}
	// connection successful
	
	// take care to either add, edit or delete and not doing all at the same time
	
	// user is able to add new entries
	if ((isset($_SESSION[$entry_add_permission]) && ($_SESSION[$entry_add_permission])) && (!isset($_GET['add'])) && (!(isset($_GET['edit']))) && (!(isset($_GET['delete']))))
	{
		echo '<div class="toolbar"><a class="button" id="new_entry" href="./?add">new entry</a></div>';
		echo "\n";
	}
	
	// overview link
	if (isset($_GET['add']) || isset($_GET['edit']) || isset($_GET['delete']))
	{
		if ($message_mode && (!((strcmp($folder, 'inbox') == 0) || (strcmp($folder, '') == 0))))
		{
			// back button might lead to the deletion form, show link to last viewed folder
			echo '<p class="simple-paging"><a class="button" href="./?folder=' . htmlspecialchars($folder) . '">overview</a><p>';
		} else
		{
			echo '<p class="simple-paging"><a class="button" href="./">overview</a></p>';
		}
	}
	
	// handle adding new item
	if ((isset($_SESSION[$entry_add_permission]) && ($_SESSION[$entry_add_permission])) && (isset($_GET['add'])) && (!(isset($_GET['edit']))) && (!(isset($_GET['delete']))))
	{
		require_once('add.php');
	}
	
	// handle editing item
	if ((isset($_SESSION[$entry_edit_permission]) && ($_SESSION[$entry_edit_permission])) && (isset($_GET['edit'])) && (!(isset($_GET['add']))) && (!(isset($_GET['delete']))))
	{
		require_once('edit.php');
	}
	
	
	// handle deleting item
	if ((isset($_SESSION[$entry_delete_permission]) && ($_SESSION[$entry_delete_permission])) && (isset($_GET['delete'])) && (!(isset($_GET['add']))) && (!(isset($_GET['edit']))))
	{
		require_once('delete.php');
	}
	
	if ((!(isset($_GET['add']))) && (!(isset($_GET['edit']))) && (!(isset($_GET['delete']))))
	{
		// show existing entries at the bottom of page
		echo '<div class="mailbox">' . "\n";
		// display depends on current mode
		if ($message_mode)
		{
			echo '<div class="msg_nav">' . "\n";
			require_once('msgUtils.php');
			$msgDisplay = new folderDisplay();
			if ((strcmp($folder, 'inbox') == 0) || (strcmp($folder, '') == 0))
			{
				// inbox displayed
				if (isset($_GET['view']))
				{
					echo '<a href="./?folder=inbox" class="active">inbox!</a>';
				} else
				{
					echo '<span>inbox!</span>';
				}
				echo ' <a href="./?folder=outbox">outbox</a>';
			} else
			{
				if (strcmp($folder, 'outbox') == 0)
				{
					// outbox displayed
					echo '<a href="./?folder=inbox">inbox</a>';
					if (isset($_GET['view']))
					{
						echo ' <a href="./?folder=outbox" class="active">outbox!</a>';
					} else
					{
						echo ' <span>outbox!</span>';
					}
				}
			}
			echo '</div>' . "\n";
			
			echo '<div class="main-box">' . "\n";
			
			if (isset($_GET['view']) && intval($_GET['view']) > 0)
			{
						
				echo '<p class="simple-paging prev_next_msg_buttons">' . "\n";
				
				// previous message button
				$query = ('SELECT `msgid` FROM `messages_users_connection` WHERE `playerid`=' . sqlSafeStringQuotes(getUserID()) . ' AND `msgid`<'
						  . sqlSafeStringQuotes($_GET['view']) . ' AND `in_inbox`='
						  . sqlSafeStringQuotes(strval((strcmp($folder, '') === 0) || (strcmp($folder, 'inbox') === 0)))
						  . ' AND `in_outbox`=' . sqlSafeStringQuotes(strval(strcmp($folder, 'outbox') === 0))
						  . ' ORDER BY `id` DESC'
						  . ' LIMIT 1');
				$result = $site->execute_query('messages_users_connection', $query, $connection);
				while ($row = mysql_fetch_array($result))
				{
					echo ('<a class="button previous" id="prev_msg" href="./?folder='
						  . htmlent($folder) . '&amp;view=' . htmlent($row['msgid'])
						  . '">Previous message</a> ');
				}
				mysql_free_result($result);
				
				// next message button
				$query = ('SELECT `msgid` FROM `messages_users_connection` WHERE `playerid`=' . sqlSafeStringQuotes(getUserID()) . ' AND `msgid`>'
						  . sqlSafeStringQuotes($_GET['view']) . ' AND `in_inbox`='
						  . sqlSafeStringQuotes(strval((strcmp($folder, '') === 0) || (strcmp($folder, 'inbox') === 0)))
						  . ' AND `in_outbox`=' . sqlSafeStringQuotes(strval(strcmp($folder, 'outbox') === 0))
						  . ' ORDER BY `id`'
						  . ' LIMIT 1');
				$result = $site->execute_query('messages_users_connection', $query, $connection);
				while ($row = mysql_fetch_array($result))
				{
					echo (' <a class="button next" id="next_msg" href="./?folder='
						  . htmlent($folder) . '&amp;view=' . htmlent($row['msgid'])
						  . '">Next message</a>');
				}
				mysql_free_result($result);
				
				echo "\n" . '</p>';
			}
			

			$msgDisplay->displayMessageFolder($folder, $connection, $site, $logged_in);
	
			echo '</div>' . "\n";
		} else
		{
			// take care the table(s) do exist and if not create them
			// FIXME: do this in maintenance
//			db_create_when_needed($site, $connection, $message_mode, $table_name, $table_name_msg_user_connection);
			
			
			// the "LIMIT 0,15" part of query means only the first fifteen entries are received
			$query = 'SELECT * FROM `' . $table_name . '` ORDER BY id DESC LIMIT ';
			$view_range = (int) 0;
			// the "LIMIT 0,15" part of query means only the first 15 entries are received
			// the range of shown matches is set by the GET variable i
			if (isset($_GET['i']))
			{
				if (((int) $_GET['i']) > 0)
				{
					$view_range = (int) $_GET['i'];
					$query .=  $view_range . ',';
				} else
				{
					// force write 0 for value 0 (speed saving due to no casting to string)
					// and 0 for negative values (security: DBMS error handling prevention)
					$query .= '0,';
				}
			} else
			{
				// no special value set -> write 0 for value 0 (speed)
				$query .= '0,';
			}
			// how many resulting rows does the user wish?
			// assume 200 by default
			$num_results = 15;
			if (isset($_GET['search']))
			{
				if (isset($_GET['search_result_amount']))
				{
					if ($_GET['search_result_amount'] > 0)
					{
						// cast result to int to avoid SQL injections
						$num_results = (int) $_GET['search_result_amount'];
						// a little try against denial of service by limiting displayed amount per page
						if ($num_results > 3200)
						{
							$num_results = 3200;
						}
					}
				}
			}
			$query .= sqlSafeString($num_results + 1);

			echo '<div class="main-box msg-box">';
			
			$result = ($site->execute_query($table_name, $query, $connection));
			if (!$result)
			{
				$site->dieAndEndPage();
			}
			
			$rows = (int) mysql_num_rows($result);
			$show_next_visits_button = false;
			if ($rows === 0)
			{
				echo '<p class="first_p">No entries made yet.</p>' . "\n";
				$site->dieAndEndPage();
			}
			// more than wished announcements per page available in total
			if ($rows > $num_results)
			{
				$show_next_visits_button = true;
			}
			unset($rows);
			
			$current_row = 0;
			// read each entry, row by row
			while ($row = mysql_fetch_array($result))
			{
				if ($current_row < $num_results)
				{
					echo '<p class="edit_and_delete_links">';
					if ((isset($_SESSION[$entry_edit_permission])) && ($_SESSION[$entry_edit_permission]))
					{
						$currentId = $row["id"];
						echo '<a class="button" href="./?edit=' . $currentId . '">edit</a>' . "\n";
					}
					if ((isset($_SESSION[$entry_delete_permission])) && ($_SESSION[$entry_delete_permission]))
					{
						$currentId = $row["id"];
						echo '<a class="button" href="./?delete=' . $currentId . '">delete</a>' . "\n";
					}
					echo '</p>' . "\n\n";
					
					
					
					echo '<div class="article">' . "\n";
					echo '<div class="article_header">' . "\n";
					echo '<div class="timestamp">';
					printf("%s", htmlentities($row["timestamp"]));
					echo '</div>' . "\n";
					echo '<div class="author">';
					echo 'By: ';
					$author = $site->forced_author($table_name);
					if (!(strcmp($author, '') === 0))
					{
						echo $author;
						// show the author to the ones who can add, edit or delete entries
						if ((isset($_SESSION[$entry_add_permission]) && ($_SESSION[$entry_add_permission]))
							|| ((isset($_SESSION[$entry_edit_permission])) && ($_SESSION[$entry_edit_permission]))
							|| ((isset($_SESSION[$entry_delete_permission])) && ($_SESSION[$entry_delete_permission])))
						{
							echo ' (' . $row["author"] . ')';
						}
							
					} else
					{
						echo $row["author"];
					}
					echo '</div>' . "\n";
					echo '</div>' . "\n";
					echo '<div class="news_body">';
					echo $row['announcement'];
					echo '</div>' . "\n";
					echo "</div>\n\n";
					$current_row++;
				}
			}
			// query result no longer needed
			mysql_free_result($result);
			unset($current_row);
			
			// look up if next and previous buttons are needed to look at all entries in overview
			if ($show_next_visits_button || ($view_range !== (int) 0))
			{
				// browse previous and next entries, if possible
				echo "\n" . '<p class="simple-paging">'  . "\n";
				
				if ($view_range !== (int) 0)
				{
					echo '	<a href="./?i=';
					
					echo ((int) $view_range)-$num_results;
					if (isset($_GET['search']))
					{
						echo '&amp;search';
						if (isset($_GET['search_string']))
						{
							echo '&amp;search_string=' . htmlspecialchars($_GET['search_string']);
						}
						if (isset($_GET['search_type']))
						{
							echo '&amp;search_type=' . htmlspecialchars($_GET['search_type']);
						}
						if (isset($num_results))
						{
							echo '&amp;search_result_amount=' . strval($num_results);
						}
					}
					
					echo '" class="previous">Previous announcements</a>' . "\n";
				}
				if ($show_next_visits_button)
				{
					
					echo '	<a href="./?i=';
					
					echo ((int) $view_range)+$num_results;
					if (isset($_GET['search']))
					{
						echo '&amp;search';
						if (isset($_GET['search_string']))
						{
							echo '&amp;search_string=' . htmlspecialchars($_GET['search_string']);
						}
						if (isset($_GET['search_type']))
						{
							echo '&amp;search_type=' . htmlspecialchars($_GET['search_type']);
						}
						if (isset($num_results))
						{
							echo '&amp;search_result_amount=' . strval($num_results);
						}
					}
					
					echo '" class="next">Next announcements</a>' . "\n";
				}
				echo '</p>' . "\n";
			}
			echo '</div>' . "\n";
			
		}
		echo '</div>' . "\n";
	}
?>

</div>
</body>
</html>