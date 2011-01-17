<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	
	require_once (dirname(dirname(__FILE__)) . '/siteinfo.php');
	$site = new siteinfo();
	
	if (strcmp($page_title, '') === 0)
	{
		$site->dieAndEndPage('Error: No page title specified!');;
	}
	
	if (isset($_GET['edit']))
	{
		$display_page_title = 'Page content editor: ' . $display_page_title;
	}
	require_once (dirname(dirname(__FILE__)) . '/index.inc');
	
	require (dirname(dirname(__FILE__)) . '/navi.inc');
	
	$site = new siteinfo();
	
	echo '<h1 class="info">' .  $display_page_title . '</h1>';
	
	function errormsg()
	{
		echo '<p>You do not have the permission to edit this content.</p>' . "\n";
	}
	
	
	// initialise variables
	$previewSeen = '';
	$content = '';
	
	
	// set their values in case the POST variables are set
	if (isset($_POST["preview"]))
	{
		$previewSeen = (int) $_POST['preview'];
	}
	if (isset($_POST['edit_page']))
	{
		// user looked at preview but chose to edit the message again
		$previewSeen = 0;
	}
	if (isset($_POST['announcement']))
	{
		$content = $_POST['announcement'];
	}
	
	
	if ((isset($_SESSION[$entry_edit_permission])) && ($_SESSION[$entry_edit_permission]))
	{
		// user has permission to edit the page
		if (isset($_GET['edit']))
		{
			// user looks at page in edit mode
			echo '<div class="simple-paging"><a href="./" class="button">overview</a></div>' ."\n";
			echo '<div class="static_page_box">' . "\n";
		} else
		{
			// user looks at page in read mode
			echo '<div class="toolbar"><a href="./?edit" class="button">edit</a></div>' . "\n";
			echo "\n";
		}
	} else
	{
		// user has no permission to edit the page
		if (isset($_GET['edit']))
		{
			// user wants to edit the page
			// show a button to let the user look at the page in read only mode
			echo '<div class="simple-paging"><a href="./" class="button">overview</a>' . '</div>' ."\n";
			// stop here or the user will be able to edit the content despite he has no permission
			errormsg();
			$site->dieAndEndPageNoBox();
		}
	}
	
	// prevent links letting people modify the page unintentionally
	if (isset($_GET['edit']) && ($previewSeen > 0))
	{
		$new_randomkey_name = '';
		if (isset($_POST['key_name']))
		{
			$new_randomkey_name = html_entity_decode($_POST['key_name']);
		}
		$randomkeysmatch = $site->compare_keys(urldecode($randomkey_name), $new_randomkey_name);
		
		if (!$randomkeysmatch)
		{
			// automatically back to main view
			echo '<p>The magic key does not match, it looks like you came from somewhere else or your session expired.';
			echo ' Going back to compositing mode.</p>' . "\n";
			$previewSeen = 0;
		}
	}
	
	function readContent($page_title, $site, $connection, &$author, &$last_modified, $raw=false)
	{
		// initialise return variable so any returned value will be always in a defined state
		$content = '';
		if (!$raw)
		{
			$content = '<p class="first_p">No content available yet.</p>';
		}
		
		$query = 'SELECT * FROM `static_pages` WHERE `page_name`=' . sqlSafeStringQuotes($page_title) . ' LIMIT 1';
		if (!($result = @$site->execute_query('static_pages', $query, $connection)))
		{
			$site->dieAndEndPage('An error occured getting content for page ' . $page_title . '!');
		}
		
		// process query result array
		while ($row = mysql_fetch_array($result))
		{	 
			$author = $row['author'];
			$last_modified = $row['last_modified'];
			if ($raw && $site->bbcode_lib_available())
			{
				$content = $row['raw_content'];
			} else
			{
				$content = $row['content'];
			}
		}
		
		mysql_free_result($result);
		
		return $content;
	}
	
	function writeContent(&$content, $page_title, $site, $connection)
	{
		if (strcmp($content, '') === 0)
		{
			// empty content
			$query = 'DELETE FROM `static_pages` WHERE `page_name`=' . sqlSafeStringQuotes($page_title);
			if (!($result = @$site->execute_query('static_pages', $query, $connection)))
			{
				$site->dieAndEndPage('An error occured deleting content for page ' . $page_title . '!');
			}
			return;
		}
		
		$query = 'SELECT `id` FROM `static_pages` WHERE `page_name`=' . sqlSafeStringQuotes($page_title) . ' LIMIT 1';
		if (!($result = @$site->execute_query('static_pages', $query, $connection)))
		{
			$site->dieAndEndPage('An error occured getting content for page ' . $page_title . '!');
		}
		
		// number of rows
		$rows = (int) mysql_num_rows($result);
		$date_format = date('Y-m-d H:i:s');
		if ($rows < ((int) 1))
		{
			// no entry in table regarding current page
			// thus insert new data
			$query = 'INSERT INTO `static_pages` (`author`, `page_name`, `raw_content`, `content`, `last_modified`) VALUES (';
			// getUserID() is a function from siteinfo.php that identifies the current user
			$query .= sqlSafeStringQuotes(getUserID());
			$query .= ', ' . sqlSafeStringQuotes($page_title);
			$query .= ', ' . sqlSafeStringQuotes($content);
			if ($site->bbcode_lib_available())
			{
				$query .= ', ' . sqlSafeStringQuotes($site->bbcode($content));
			} else
			{
				$query .= ', ' . sqlSafeStringQuotes($content);
			}
			$query .= ', ' . sqlSafeStringQuotes($date_format);
			$query .= ')';
		} else
		{
			// either 1 or more entries found, just assume there is only one
			$query = 'UPDATE `static_pages` SET `author`=' . sqlSafeStringQuotes(getUserID());
			$query .= ', `raw_content`=' . sqlSafeStringQuotes($content);
			if ($site->bbcode_lib_available())
			{
				$query .= ', `content`=' . sqlSafeStringQuotes($site->bbcode($content));
			} else
			{
				$query .= ', `content`=' . sqlSafeStringQuotes($content);
			}			
			$query .= ', `last_modified`=' . sqlSafeStringQuotes($date_format);
			$query .= ' WHERE `page_name`=' . sqlSafeStringQuotes($page_title);
			$query .= ' LIMIT 1';
		}
		
		if (!($result = @$site->execute_query('static_pages', $query, $connection)))
		{
			$site->dieAndEndPage('An error occured updating content for page ' . $page_title
								 . ' by user ' . sqlSafeString(getUserID()) . '!');
		}
	}
	
	if ($previewSeen === 2)
	{
		writeContent($content, $page_title, $site, $connection);
		echo '<p>Updating: No problems occured, changes written successfully!</p>' . "\n";
		// we are done updating, do not show the edit field again
		$site->dieAndEndPage();
	}
		
	if (isset($_GET['edit']))
	{
		echo '<form action="./?edit" enctype="application/x-www-form-urlencoded" method="post" accept-charset="utf-8">' . "\n";
		$new_randomkey_name = $randomkey_name . microtime();
		$new_randomkey = $site->set_key($new_randomkey_name);
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="key_name" value="' . htmlentities($new_randomkey_name) . '"');
		echo '</div>' . "\n";
		echo '<div>';
		$site->write_self_closing_tag('input type="hidden" name="' . htmlentities($randomkey_name) . '" value="'
									  . urlencode(($_SESSION[$new_randomkey_name])) . '"');
		echo '</div>' . "\n";
		
		if ($previewSeen === 1)
		{
			echo '<p>Preview:</p>' . "\n";
			echo '<div>';
			if ($site->bbcode_lib_available())
			{
				echo $site->bbcode($content);
			} else
			{
				echo $content;
			}
			echo '</div>' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="announcement" value="' . htmlent($content) . '"');
			echo '</div>' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="preview" value="2"');
			echo '</div>' . "\n";
			echo '</div>' . "\n";
			
			echo '<p>';
			$site->write_self_closing_tag('input type="submit" value="Confirm changes" class="button"');
			$site->write_self_closing_tag('input type="submit" name="edit_page" value="Edit page" class="button"');
			echo '</p>' . "\n";
			
			$site->dieAndEndPageNoBox();
		} else
		{
			if ($site->bbcode_lib_available())
			{
				echo '<div>Keep in mind to use BBCode instead of HTML or XHTML.</div>' . "\n";
				echo '<div>';
				include dirname(dirname(__FILE__)) . '/bbcode_buttons.php';
				$bbcode = new bbcode_buttons();
				$bbcode->showBBCodeButtons();
				unset($bbcode);
				echo '</div>';
			} else
			{
				if ($site->use_xtml())
				{
					echo '<div>Keep in mind the home page currently uses XHTML, not HTML or BBCode.</div>' . "\n";
				} else
				{
					echo '<div>Keep in mind the home page currently uses HTML, not XHTML or BBCode.</div>' . "\n";
				}
			}
			$buffer = '';
			if (isset($_POST['edit_page']))
			{
				$buffer = $_POST['announcement'];
			} else
			{
				$buffer = readContent($page_title, $site, $connection, $author, $last_modified, true);
			}
			echo '<div><textarea cols="75" rows="20" name="announcement">' . htmlent($buffer) . '</textarea></div>' . "\n";
			echo '<div>';
			$site->write_self_closing_tag('input type="hidden" name="preview" value="1"');
			echo '</div>' . "\n";
			
			echo '<p>';
			$site->write_self_closing_tag('input type="submit" value="Preview" class="button"');
			echo '</p>' . "\n";
		}
		echo '</form>' . "\n";
	} else
	{
		switch ($cmspage) 
		{
			case 'home'  : 
			{
				echo '<div class="homepage">';
				echo '<div class="home-left">'; 
				if ($logged_in) 
				{
					put_shoutbox();
				}
				
				echo '<div class="main-box">' . "\n";
				$author = '';
				$last_modified = '';				
				$buffer = readContent($page_title, $site, $connection, $author, $last_modified);
				echo $buffer;			
				echo '</div>';	
				echo '</div>';
				include dirname(dirname(dirname(__FILE__))) . '/right_column.php';
				echo '</div>';
				
			} break;
			default: 
			{
				echo '<div class="static_page_box">' . "\n";
				$author = '';
				$last_modified = '';
				
				$buffer = readContent($page_title, $site, $connection, $author, $last_modified);
				echo $buffer;	
				echo '</div>';

				
			}
		}
		
		
	}
	
function put_shoutbox()
{

	$token = md5(uniqid(rand(), true));
	$_SESSION['token'] = $token;
	

	
?>	

<script type="text/javascript" src="wtag/js/dom-drag.js"></script>
<script type="text/javascript" src="wtag/js/scroll.js"></script>
<script type="text/javascript" src="wtag/js/conf.js"></script>
<script type="text/javascript" src="wtag/js/ajax.js"></script>
<script type="text/javascript" src="wtag/js/drop_down.js"></script>


<div id="chat" class="main-box home-box">


<div id="container">
<div id='content'>
</div>
</div>

<div id='form'>
<form id="cform" name="cform" action="#" >
<div id='field_set'>
<input type='hidden' id='token' name='token' value='<?php echo $token; ?>' />
<textarea rows='1' id='message'  name='message' >message</textarea>
<div id="chat_menu">

<div id='emo'>
<ul id="show_sm">
<li>
smileys
<ul id="smiley_box">
<li>
<img class='smileys' src='wtag/smileys/smile.gif' width='15' height='15' alt=':)' title=':)' onclick = "tagSmiley(':)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/sad.gif' width='15' height='15' alt=':(' title=':(' onclick = "tagSmiley(':(');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/wink.gif' width='15' height='15' alt=';)' title=';)' onclick = "tagSmiley(';)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/tongue.gif' width='15' height='15' alt=':-P' title=':-P' onclick = "tagSmiley(':-P');"/>
</li>
<li>
<img class='smileys' src='wtag/smileys/rolleyes.gif' width='15' height='15' alt='S-)' title='S-)' onclick = "tagSmiley('S-)');"/>
</li>
<li>
<img class='smileys' src='wtag/smileys/angry.gif' width='15' height='15' alt='>(' title='>(' onclick = "tagSmiley('>(');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/embarassed.gif' width='15' height='15' alt=':*)' title=':*)' onclick = "tagSmiley(':*)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/grin.gif' width='15' height='15' alt=':-D' title=':-D' onclick = "tagSmiley(':-D');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/cry.gif' width='15' height='15' alt='QQ' title='QQ' onclick = "tagSmiley('QQ');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/shocked.gif' width='15' height='15' alt='=O' title='=O' onclick = "tagSmiley('=O');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/undecided.gif' width='15' height='15' alt='=/' title='=/' onclick = "tagSmiley('=/');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/cool.gif' width='15' height='15' alt='8-)' title='8-)' onclick = "tagSmiley('8-)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/sealedlips.gif' width='15' height='15' alt=':-X' title=':-X' onclick = "tagSmiley(':-X');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/angel.gif' width='15' height='15' alt='O:]' title='O:]' onclick = "tagSmiley('O:]');" />
</li>
</ul>
</li>

</ul>
</div>

<div id='submit'>
<p>submit</p>
</div>

</div>
</div>
</form>
</div> 



</div>


<?php 
	
}
	
	
?>

</div>
</body>
</html>