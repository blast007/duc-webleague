<?php
	if (!isset($site))
	{
		die('This file is meant to be only included by other files!');
	}
	
	// edit profile
	if (isset($_GET['edit']))
	{
		// edit profile page
		$profile = (int) $_GET['edit'];
		
		show_overview_and_profile_button();
		
		echo '<div class="main-box">' . "\n";
		if ($profile === 0)
		{
			echo '<p class="error-msg">The user id 0 is reserved for not logged in players and thus no user with that id could ever exist.</p>' . "\n";
			$site->dieAndEndPage();
		}
		
		if (!(($profile === $viewerid) || $allow_edit_any_user_profile))
		{
			$site->dieAndEndPage('You (id='. $viewerid. ') are not allowed to edit the profile of the user with id ' . sqlSafeString($profile));
		}
		
		$suspended_status = 'deleted';
		if (!(isset($_POST['user_suspended_status_id'])))
		{
			// get entire suspended status, including maintenance-deleted
			$query = 'SELECT `status`';
//			if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
//			{
				// the ones who can ban, can also change a user's callsign
				$query .= ',`name`';
//			}
			$query .= ' FROM `players` WHERE `id`=' . "'" . (urlencode($profile)) ."'";
			// only information about one player needed
			$query .= ' LIMIT 1';
			if (!($result = @$site->execute_query('players', $query, $connection)))
			{
				$site->dieAndEndPage();
			}
			// inisialise name with error message, it will get overwritten if a name can be found
			$callsign = 'ERROR: unknown callsign';
			while($row = mysql_fetch_array($result))
			{
				$suspended_status = $row['status'];
				$callsign = htmlent($row['name']);
			}
			mysql_free_result($result);
			
			if (strcmp($suspended_status, 'deleted') === 0)
			{
				echo '<p class="error-msg">You may not edit this user as the user was deleted during maintenance.</p>';
				$site->dieAndEndPage('');
			}
		}		
		
		if (isset($_POST['confirmed']))
		{
			// someone is trying to break the form
			// TODO: implement preview
			if (($_POST['confirmed'] < 1) || ($_POST['confirmed'] > 2))
			{
				$site->dieAndEndPage('Your (id='. $viewerid. ') attempt to insert wrong data into the form was detected.');
			}
						
			$new_randomkey_name = '';
			if (isset($_POST['key_name']))
			{
				$new_randomkey_name = html_entity_decode($_POST['key_name']);
			}
			$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
			
			if (!($randomkeysmatch))
			{
				echo '<p class="error-msg">The key did not match. It looks like you came from somewhere else.</p>';
				$site->dieAndEndPage('');
			}
			
			// could callsign change be requested?
			if (isset($_POST['callsign']))
			{
				// only admins can edit their comments
				if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
				{
					// is the player name already used?
					$query = 'SELECT `id` FROM `players` WHERE `name`=' . sqlSafeStringQuotes(htmlent($_POST['callsign'])) . ' LIMIT 1';
					if (!($result = @$site->execute_query('players', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
					
					if ((int) mysql_num_rows($result) > 0)
					{
						$tmp_name_change_requested = true;
						if (!((int) mysql_num_rows($result) > 1))
						{
							while ($row = mysql_fetch_array($result))
							{
								if (((int) $row['id']) === ((int) $profile))
								{
									$tmp_name_change_requested = false;
								}
							}
						}
						mysql_free_result($result);
						
						// was callsign change requested?
						if ($tmp_name_change_requested)
						{
							// callsign change was requested!
							// player name already used -> do not change to player name to it
							echo '<p class="error-msg">The player name was not changed because there is already a player with that name in the database.</p>' . "\n";
						}
						unset($tmp_name_change_requested);
					} else
					{
						mysql_free_result($result);
						$query = 'UPDATE `players` SET `name`=' . sqlSafeStringQuotes(htmlent(urldecode($_POST['callsign'])));
						$query .= ' WHERE `id`=' . sqlSafeStringQuotes($profile);
						if (!($result = @$site->execute_query('players_profile', $query, $connection)))
						{
							// query was bad, error message was already given in $site->execute_query(...)
							$site->dieAndEndPage('');
						}
					}
				}
			}
			
			if (isset($_POST['location']))
			{
				$query = 'SELECT `location` FROM `players_profile` WHERE `playerid`=' . sqlSafeStringQuotes($profile) . ' LIMIT 1';
				if (!($result = @$site->execute_query('players_profile', $query, $connection)))
				{
					$site->dieAndEndPageNoBox('Could not confirm value ' . sqlSafeStringQuotes($_POST['location']) . ' as new location.');
				}
				$update_location = false;
				while ($row = mysql_fetch_array($result))
				{
					if (!(((int) $_POST['location']) === ((int) $row['location'])))
					{
						$update_location = true;
					}
				}
				mysql_free_result($result);
				if ($update_location)
				{
					$query = 'UPDATE `players_profile` SET `location`=' . sqlSafeStringQuotes((int) $_POST['location']);
					$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
					if (!($result = @$site->execute_query('players_profile', $query, $connection)))
					{
						$site->dieAndEndPageNoBox('Could not update value '
												  . sqlSafeStringQuotes($_POST['location'])
												  . ' as new location for player '
												  . sqlSafeStringQuotes($profile) . '.');
					}
				}
			}
			
			if (isset($_POST['timezone']))
			{
				$query = 'UPDATE `players_profile` SET `UTC`=' . sqlSafeStringQuotes(intval($_POST['timezone']));
				$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
				if (!($result = @$site->execute_query('players_profile', $query, $connection)))
				{
					$site->dieAndEndPageNo('Could not set timezone for player with id '
										   . sqlSafeStringQuotes($profile)
										   . '.');
				}
			}
			
			// is there a user comment?
			if (isset($_POST['user_comment']))
			{
				if (!(strcmp($_POST['user_comment'], 'No profile text has yet been set up') === 0))
				{
					// yes there is a comment, save it!
					$query = 'UPDATE `players_profile` SET `user_comment`=' . sqlSafeStringQuotes($site->bbcode($_POST['user_comment']));
					$query .= ', `raw_user_comment`=' . sqlSafeStringQuotes($_POST['user_comment']);
					$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
					if (!($result = @$site->execute_query('players_profile', $query, $connection)))
					{
						$site->dieAndEndPage('');
					}
				}
			}
			
			if (isset($_POST['logo_url']))
			{
				$allowedExtensions = array('.png', '.bmp', '.jpg', '.gif', 'jpeg');
				$logo_url = sqlSafeString($_POST['logo_url']);
				if ((in_array(substr($logo_url, -4), $allowedExtensions)) && (substr($logo_url, 0, 7) == 'http://'))
				{
					// image url exists and has a valid file extension
					$query = "UPDATE `players_profile` SET `logo_url` = '$logo_url'";
					$query .= " WHERE `playerid` = $profile";
					if (!($result = $site->execute_query('players_profile', $query, $connection)))
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
			
			if (isset($_POST['admin_comments']))
			{
				// only admins can edit their comments
				if ($allow_add_admin_comments_to_user_profile)
				{
					$query = 'UPDATE `players_profile` SET `admin_comments`=' . sqlSafeStringQuotes($site->bbcode($_POST['admin_comments']));
					$query .= ', `raw_admin_comments`=' . sqlSafeStringQuotes($_POST['admin_comments']);
					$query .= ' WHERE `playerid`=' . sqlSafeStringQuotes($profile);
					if (!($result = @$site->execute_query('players_profile', $query, $connection)))
					{
						// query was bad, error message was already given in $site->execute_query(...)
						$site->dieAndEndPage('');
					}
				}
			}
			
			echo '<p>The player profile has been updated successfully.</p>' . "\n";
			$site->dieAndEndPage('');
		}
		
		// display editing form
		echo '<form enctype="application/x-www-form-urlencoded" method="post" action="?edit=' . $profile . '" class="player-form">' . "\n";
		echo '<input type="hidden" name="confirmed" value="1">' . "\n";
		$new_randomkey_name = $randomkey_name . microtime();
		$new_randomkey = $site->set_key($new_randomkey_name);
		echo '<input type="hidden" name="key_name" value="' . htmlspecialchars($new_randomkey_name) . '">' . "\n";
		echo '<input type="hidden" name="' . htmlspecialchars($randomkey_name) . '" value="';
		echo urlencode(($_SESSION[$new_randomkey_name])) . '">' . "\n";
		
		$query = 'SELECT `location`, `UTC`';
		$query .= ', `raw_user_comment`, `raw_admin_comments`';
		$query .= ', `logo_url` FROM `players_profile` WHERE `playerid`=' . "'" . sqlSafeString($profile) . "'";
		$query .= ' LIMIT 1';
		if (!($result = @$site->execute_query('players_profile', $query, $connection)))
		{
			// query was bad, error message was already given in $site->execute_query(...)
			$site->dieAndEndPage('');
		}
		
		$location = 0;
		$timezone = 0;
		$user_comment = '';
		$admin_comments = '';
		while ($row = mysql_fetch_array($result))
		{
			$location = (int) $row['location'];
			$timezone = (int) $row['UTC'];
			$user_comment = $row['raw_user_comment'];
			$admin_comments = $row['raw_admin_comments'];
			$logo_url = $row['logo_url'];
		}
		mysql_free_result($result);
		
		// show some sort of comment because one would expect some profile text
		// admin comments in contrary should not be set often and thus just ignore the default to make sure it does not get set by accident
		if (strcmp($user_comment, '') === 0)
		{
			$user_comment = 'No profile text has yet been set up';
		}
		
		// admins may change user names
		if (isset($_SESSION['allow_ban_any_user']) && $_SESSION['allow_ban_any_user'])
		{
			echo '<div class="formrow"><label class="player_edit" for="edit_player_name">Change callsign:</label> ';
			$site->write_self_closing_tag('input id="edit_player_name" type="text" name="callsign" maxlength="50" size="60" value="'
										  . htmlent_decode($callsign) . '"');
			echo '</div>';
		}
		
		// location
		$query = 'SELECT `id`,`name` FROM `countries` ORDER BY `name`';
		if (!($result = @$site->execute_query('countries', $query, $connection)))
		{
			$site->dieAndEndPage('Could not retrieve list of countries from database.');
		}
		echo '<div class="formrow"><label class="player_edit" for="edit_player_location">Change country:</label> ';
		echo '<select id="edit_player_location" name="location">';
		while ($row = mysql_fetch_array($result))
		{
			echo '<option value="';
			echo htmlspecialchars($row['id']);
			if ($location === ((int) $row['id']))
			{
				echo '" selected="selected';
			}
			echo '">';
			echo htmlent($row['name']);
			echo '</option>' . "\n";
		}
		mysql_free_result($result);
		echo '</select>';
		echo '</div>' . "\n\n";
		
		// timezone
		echo '<div class="formrow"><label class="player_edit" for="edit_player_location">Change timezone:</label> ';
		echo '<select id="edit_player_timezone" name="timezone">';
		for ($i = -12; $i <= 12; $i++)
		{
			echo '<option value="';
			echo htmlspecialchars($i);
			if ($timezone === $i)
			{
				echo '" selected="selected';
			}
			echo '">';
			if ($i >= 0)
			{
				$time_format = '+' . strval($i);
			} else
			{
				$time_format = strval($i);
			}
			echo htmlent('UTC ' . $time_format);
			echo '</option>' . "\n";
		}
		unset($time_format);
		echo '</select>';
		echo '</div>' . "\n\n";
		
		// user comment
		if ($site->bbcode_lib_available())
		{
			echo "\n" . '<div class="formrow">';
			echo '	<label class="player_edit invisi">bbcode:</label><span>';
			include dirname(dirname(__FILE__)) . '/CMS/bbcode_buttons.php';
			$bbcode = new bbcode_buttons();
			$bbcode->showBBCodeButtons('user_comment');
			unset($bbcode);			
			echo '</span>';
			echo "\n";
			echo '</div>' . "\n";
		}
		echo '<div class="formrow"><label class="player_edit" for="edit_user_comment">User comment: </label>' . "\n";
		echo '<span><textarea class="player_edit" id="edit_user_comment" rows="10" cols="50" name="user_comment">';
		echo $user_comment;
		echo '</textarea></span></div>';

		// logo/avatar url
		echo '<div class="formrow"><label class="player_edit" for="edit_avatar_url">Avatar URL: </label>';
		$site->write_self_closing_tag('input id="edit_avatar_url" type="text" name="logo_url" maxlength="200" size="60" value="'.$logo_url.'"');
		echo '</div>';
		
		// admin comments, these should only be set by an admin
		if ($allow_add_admin_comments_to_user_profile === true)
		{
			if ($site->bbcode_lib_available())
			{
				echo "\n" . '<div class="formrow">';
				echo '	<label class="player_edit invisi">bbcode:</label><span>';
				// bbcode_buttons.php file already included
				$bbcode = new bbcode_buttons();
				$bbcode->showBBCodeButtons('admin_comments');
				unset($bbcode);
				echo '</span>';
				echo "\n";
				echo '</div>' . "\n";
			}
			echo '<div class="formrow"><label class="player_edit" for="edit_admin_comments">Edit admin comments: </label>';
			echo '<span><textarea class="player_edit" id="edit_admin_comments" rows="10" cols="50" name="admin_comments">';
			echo $admin_comments;
			echo '</textarea></span></div>' . "\n";
		}
		
		echo '<div><input type="submit" name="edit_user_profile_data" value="Change user profile" id="send" class="button l15"></div>' . "\n";
		echo '</form>' . "\n";
		
		$site->dieAndEndPageNoBox('');
	}
?>