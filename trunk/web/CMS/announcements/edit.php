<?php
	// this file handles editing new entries in table $table_name of database
	
	// check again for editing entry permission, just in case
	if (isset($_SESSION[$entry_edit_permission]) && ($_SESSION[$entry_edit_permission]))
	{
		if (isset($_GET['edit']))
		{
			// cast the entry in order to close possible security holes
			$currentId = (int) ($_GET['edit']);
			// starting index in databse is 1
			if ($currentId < 1)
			{
				$currentId = 1;
			}
			
			
			$announcement = '';
			if (isset($_POST['announcement']))
			{
				$announcement = (htmlent_decode(urldecode($_POST['announcement'])));
			}
			$timestamp = '';
			if (isset($_POST['timestamp']))
			{
				$timestamp = (htmlent_decode(urldecode($_POST['timestamp'])));
			}
			$author = '';
			if (isset($_POST['author']))
			{
				$author = (htmlent_decode(urldecode($_POST['author'])));
			}
			
			// handle shown author of entry
			if ($_SESSION[$author_change_allowed])
			{
				if (!(isset($author)))
				{
					$author = $_SESSION['username'];
				}
			} 
			/* seems it should left oryginal author there, so I commented it (osta)
			else
			{
				$author = $_SESSION['username'];
			}
			*/
			if (!(isset($author)))
			{
				// no anonymous posts and therefore cancel request
				$previewSeen = 0;
			}
			
			// make sure the magic key matches
			// each form has a unique id to prevent accepting the same information twice
			// the latter could be done by users clicking on forms somewhere else
			$new_randomkey_name = '';
			if (isset($_POST['key_name']))
			{
				$new_randomkey_name = html_entity_decode($_POST['key_name']);
			}
			$randomkeysmatch = $site->compare_keys($randomkey_name, $new_randomkey_name);
			
			if (!$randomkeysmatch && $previewSeen > 1)
			{
				echo '<p>The magic key does not match, it looks like you came from somewhere else or your session expired.';
				echo ' Going back to compositing mode.</p>' . "\n";
				$previewSeen = 0;
			}
			
			// $previewSeen === 2 means we're about to insert the data
			if (($previewSeen === 2) && $randomkeysmatch)
			{
				echo '<div class="static_page_box">' . "\n";
				// sqlSafeString is from siteinfo.php
				// needed to prevent SQL injections
				// example: UPDATE `news` SET `timestamp`="tes", `author`="me", `announcement`="<b>really</b>", `raw_announcement`='[b]really[/b]' WHERE id=7
				$query = 'UPDATE `' . $table_name . '` SET `timestamp`="' . sqlSafeString($timestamp) . '", `author`="' . sqlSafeString($author);
				$query .= '", `announcement`="' . sqlSafeString($site->bbcode($announcement)) . '", `raw_announcement`="' . sqlSafeString($announcement);
				$query .= '" WHERE id=' . sqlSafeString($currentId);
				
				if ((@$site->execute_query($table_name, $query, $connection)))
				{
					echo '<p>Updating: No problems occured, changes written!</p>' . "\n";
				} else
				{
					echo '<p>Seems like editing failed.</p>' . "\n";
				}
				$site->dieAndEndPage();
			}
			
			$pfad = (pathinfo(realpath('./')));
			$name = $pfad['basename'];
			
			//\x22 == "
			if ($previewSeen === 1)
			{
				echo "<form action=\x22" . baseaddress() . $name . '/?edit=' . $currentId . "\x22 method=\x22post\x22>\n";
				echo '<div class="main-box msg-box">';
				echo '<h2>Preview:</h2>' . "\n";
				
				// We are doing the preview by echoing the info
				echo '<div class="article">' . "\n";
				echo '<div class="article_header">' . "\n";
				echo '<div class="timestamp">';
				echo htmlent($timestamp);
				echo '</div>' . "\n";
				echo '<div class="author"> By: ';
				echo htmlent($author);
				echo '</div>' . "\n";
				echo '</div>' . "\n";
				echo '<p>' . $site->bbcode($announcement) . '</p>' . "\n";
				echo '</div>' . "\n\n";
				
				// keep the information in case user confirms by using invisible form items
				$site->write_self_closing_tag('input type="hidden" name="announcement" value="' . urlencode(htmlent($announcement)) . '"');		
				$site->write_self_closing_tag('input type="hidden" name="preview" value="2"');
				$site->write_self_closing_tag('input type="hidden" name="timestamp" value="' . urlencode(htmlent($timestamp)) . '"');
				$site->write_self_closing_tag('input type="hidden" name="author" value="' . urlencode(htmlent($author)) . '"');
				$site->write_self_closing_tag('input type="hidden" name="announcement" value="' . urlencode(htmlent($announcement)) . '"');
				
				$new_randomkey_name = $randomkey_name . microtime();
				$new_randomkey = $site->set_key($new_randomkey_name);
				$site->write_self_closing_tag('input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"');
				$site->write_self_closing_tag('input type="hidden" name="' . sqlSafeString($randomkey_name) . '" value="'
											  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
				
				echo "\n";
				echo '<p class="simple-paging">';
				$site->write_self_closing_tag('input type="submit" value="Confirm changes" class="button"');
				echo '</p>' . "\n";
				echo '</div>';
			} else
			{
				// $previewSeen === 0 means we just decided to add something but did not fill it out yet
				if ($previewSeen === 0)
				{
					echo '<div class="static_page_box">' . "\n";
					
					$query = ('SELECT `timestamp`,`author`,`raw_announcement` from `' . $table_name
							  . '` WHERE `id`=' . sqlSafeStringQuotes($currentId) . ' ORDER BY id LIMIT 1');
					$result = ($site->execute_query($table_name, $query, $connection));
					if (!$result)
					{
						$site->dieAndEndPage();
					}
					
					// read each entry, row by row
					while($row = mysql_fetch_array($result))
					{
						// overwrite each variable at first request of editing
						// these values come right from the database so perform no sanity checks
						$timestamp = $row['timestamp'];
						$author = $row['author'];
						$announcement = $row['raw_announcement'];
					}
					// done
					mysql_free_result($result);
					
					
					echo '<form action="' . baseaddress() . $name . '/?edit=' . $currentId . '" method="post">' . "\n";
					
					// timestamp
					echo '<div>' . "\n";
					echo '	<label class="msg_edit" for="msg_edit_timestamp">Timestamp:</label> ' . "\n";
					echo '	<span>' . "\n" . '		';
					$site->write_self_closing_tag('input type="text" id="msg_edit_timestamp" name="timestamp" value="'
												  . htmlentities(urldecode($timestamp)) . '"');
					echo '	</span>' . "\n";
					echo '</div>' . "\n";
					
					// announcement
					if ($site->bbcode_lib_available())
					{
						echo "\n" . '<div class="msg_edit">';
						echo '<div class="invisi" style="display: inline;">';
						echo '	<label class="msg_edit">bbcode:</label>';
						echo '</div>';
						include dirname(dirname(__FILE__)) . '/bbcode_buttons.php';
						$bbcode = new bbcode_buttons();
						$bbcode->showBBCodeButtons();
						unset($bbcode);
						echo "\n";
						echo '</div>' . "\n";
					}
					
					echo '<div>' . "\n";
					echo '	<label class="msg_edit" for="msg_send_announcement">Message:</label>' . "\n";
					echo '	<span><textarea id="msg_send_announcement" rows="2" cols="30" name="announcement">';
					echo htmlent($announcement);
					echo '</textarea></span>' . "\n";
					echo '</div>' . "\n";
					
					// author
					if ((isset($_SESSION[$author_change_allowed])) && ($_SESSION[$author_change_allowed]))
					{
						echo '<div>' . "\n";
						echo '	<label class="msg_edit" for="msg_send_subject">Author:</label>' . "\n";
						echo '	<span>';
						$site->write_self_closing_tag('input type="text" id="msg_send_subject" maxlength="50" name="author" value="' . htmlent($author) . '"'
													  . ' onfocus="if(this.value==' . "'" . htmlent($author) . "'" . ') this.value=' . "'" . "'" . '"'
													  . ' onblur="if(this.value==' . "'" . "'" . ') this.value=' . "'" . htmlent($author) . "'" . '"');
						echo "\n" . '</span>' . "\n";
						echo '</div>' . "\n";
					} else
					{
						$site->write_self_closing_tag('input type="hidden" name="author" value="' . htmlent($author) . '"');						
					}
										
					$site->write_self_closing_tag('input type="hidden" name="preview" value="' . '1' . '"');
					echo '<div class="msg_buttons">' . "\n";
					$site->write_self_closing_tag('input type="submit" value="' . 'Preview' . '"');
					echo '</div>' . "\n";
				}
			}
			
			// if there was a form opened, close it now
			if (($previewSeen == 0) || ($previewSeen === 1))
			{
				echo '</form>' . "\n";
				if (!($previewSeen === 1))
				{
					echo '</div>' . "\n";
				}
			}
		}
	}
?>