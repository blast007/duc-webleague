<div id="top">
<div class="logo"></div>

<ul class="navigation">
<?php
	require_once 'siteinfo.php';
	require_once 'permissions.php';
	if (!isset($site))
	{
		$site = new siteinfo();
	}
	
	function dieCustom($reason)
	{
		global $site;
		
		$site->log_error($reason);
		echo $reason . "\n";
		echo '</ul>' . "\n" . '</body>' . "\n" . '<html>' . "\n";
		die();
	}
	
	$unread_messages = false;
	
	// set the date and time
	date_default_timezone_set($site->used_timezone());
	
	$connection = $site->connect_to_db();
	
	
	
	
	// remove expired sessions from the list of online users
	$query ='SELECT `playerid`, `last_activity` FROM `online_users`';
	if (!($result = @$site->execute_silent_query('online_users', $query, $connection)))
	{
		// query was bad, error message was already given in $site->execute_query(...)
		$site->dieAndEndPage('<p>Could not get list of online users from database.</p>');
	}
	if (((int) mysql_num_rows($result)) > 0)
	{
		while($row = mysql_fetch_array($result))
		{
			$saved_timestamp = $row['last_activity'];
			$old_timestamp = strtotime($saved_timestamp);
			$now = (int) strtotime("now");
			// is entry more than two hours old? (60*60*2)
			// FIXME: would need to set session expiration date directly inside code
			// FIXME: and not in the webserver setting
			if ($now - $old_timestamp > (60*60*2))
			{
				$query = 'DELETE LOW_PRIORITY FROM `online_users` WHERE `last_activity`=';
				$query .= sqlSafeStringQuotes($saved_timestamp);
				if (!($result_delete = @$site->execute_silent_query('online_users', $query, $connection)))
				{
					$site->dieAndEndPage('<p>Could delete old online users from database.</p>');
				}
			}
		}
	}
	mysql_free_result($result);
	
	// update activity data
	$logged_in = true;
	if (getUserID() > 0)
	{
		// the execution of the query is not that time critical and it happens often -> LOW_PRIORITY
		$query = 'UPDATE LOW_PRIORITY `online_users` SET `last_activity`=';
		$query .= sqlSafeStringQuotes(date('Y-m-d H:i:s')) . ' WHERE `playerid`=' . sqlSafeStringQuotes(getUserID());
		@mysql_select_db($site->db_used_name(), $connection);
		@mysql_query($query, $connection);
		
		// are there unread messages?
		// are there unread messages?
		$query = ('SELECT `id` FROM `messages_users_connection` WHERE `msg_status`='
				  . sqlSafeStringQuotes('new')
				  . ' AND `playerid`=' . sqlSafeStringQuotes(getUserID())
				  . ' LIMIT 1');
		$result = @mysql_query($query, $connection);
		$rows = (int) @mysql_num_rows($result);
		if ($rows > 0)
		{
			$unread_messages = true;
		}
		mysql_free_result($result);
	} else
	{
		$logged_in = false;
	}
	
	define ('BASEPATH', baseaddress());
	
	function writeLink($folder, $title, $current=false, $classname="")
	{
		echo '<li class="' . $classname . '">';
		if (!$current)
		{
			echo '<a href="' . (BASEPATH . $folder) . '">';
		} elseif (count($_GET) > 0)
		{
			echo '<a class="current_nav_entry" href="' . (BASEPATH . $folder) . '">';
		} else echo '<span>'; 
		
		echo $title;
		if (!$current || (count($_GET) > 0))
		{
			echo '</a>';
		} else echo '</span>'  ;
		echo '</li>' . "\n";
	}
	
	function writeexternalLink($url, $title, $classname="")
	{
		echo '<li class="' . $classname . '">';
			echo '<a href="' . $url . '">';
			echo $title;
			echo '</a>';
		echo '</li>' . "\n";
	}
	
	$name = $site->base_name();
	
	// public_html on FreeBSD or Sites on Mac OS X
	$topDir = 'public_html';
	// top level dir depends on siteconfig
	$pos = strrpos(dirname(dirname(__FILE__)), '/');
	if ($pos !== false)
	{
		$topDir = substr(dirname(dirname(__FILE__)), $pos+1);;
	}
	$topDir = strcmp($name, $topDir) === 0;
	

	
	if ($topDir)
	{
		echo '<li class="home"><span>Home</span></li>' . "\n";
	} else
	{
		echo '<li class="home"><a href="' . BASEPATH . '">Home</a></li>' . "\n";
	}
	
	writeLink('News/', 'News', (strcmp($name, 'News') == 0), 'news');
	
	writeLink('Players/', 'Players', (strcmp($name, 'Players') == 0), 'players');
	
	writeLink('Teams/', 'Teams', (strcmp($name, 'Teams') == 0), 'teams');
	
	writeLink('Matches/', 'Matches', (strcmp($name, 'Matches') == 0), 'matches');
		
	writeLink('Season/', 'Seasons', (strcmp($name, 'Season') == 0), 'seasons');
	
?>	
<li class="info">
	<span>Info</span>
	<ul>
<?php 
	
	writeLink('Rules/', 'Rules', (strcmp($name, 'Rules') == 0));
	
	writeLink('FAQ/', 'FAQ', (strcmp($name, 'FAQ') == 0));
	
	writeLink('Links/', 'Links', (strcmp($name, 'Links') == 0));
	
	writeLink('Bans/', 'Bans', (strcmp($name, 'Bans') == 0));
	
	writeLink('Contact/', 'Contact', (strcmp($name, 'Contact') == 0));
	
?>
	</ul>
</li>
<li class="tools">
	<span>Tools</span>
	<ul>
<?php 
	
	if ($logged_in && (isset($_SESSION['allow_watch_servertracker'])) && ($_SESSION['allow_watch_servertracker']))
	{
		writeLink('Servertracker/', 'Servers', (strcmp($name, 'Servertracker') == 0));
	}
	
	if ($logged_in && isset($_SESSION['viewerid']))
	{
		writeLink('Players/?profile=' . $_SESSION['viewerid'], 'My profile', (strcmp($name, 'My profile') == 0));
	}
	
	if ($logged_in && isset($_SESSION['viewerid']))
	{
		writeLink('Spawnlist/', 'Spawnlist', (strcmp($name, 'Spawnlist') == 0));
	}
	
	writeLink('Config/', 'Config', (strcmp($name, 'Config') == 0));
	
	if ($logged_in && (isset($_SESSION['allow_view_user_visits'])) && ($_SESSION['allow_view_user_visits']))
	{
		writeLink('Visits/', 'Visits', (strcmp($name, 'Visits') == 0));
	}
	
	if ($logged_in && isset($_SESSION['allow_moderate_shoutbox']) && $_SESSION['allow_moderate_shoutbox'])
	{
		writeLink('Shoutbox/', 'Shoutbox', (strcmp($name, 'Shoutbox') == 0));
	}
	
	if ($logged_in && isset($_SESSION['allow_manage_sitebans']) && $_SESSION['allow_manage_sitebans'])
	{
		writeLink('Bans/sitebans.php', 'Sitebans', (strcmp($name, 'Sitebans') == 0));
	}
	
	
?>

	</ul> 
</li> 

<?php 
		writeexternalLink('http://my.bzflag.org/bb/viewforum.php?f=90', 'Forum', 'forum');


?>

</ul>

<ul class="small-navigation">
<?php 
 
	if ((isset($_SESSION['user_logged_in'])) && ($_SESSION['user_logged_in']))
	{
		if ($unread_messages)
		{
			writeLink('Messages/', 'Mail', false, 'mail unread');
		} else
		{
			writeLink('Messages/', 'Mail', (strcmp($name, 'Messages') == 0), 'mail');
		}
	}
		
	if (!$logged_in)
	{
		echo '<li><a href=';
		$url = urlencode(baseaddress() . 'Login/' . '?bzbbauth=%TOKEN%,%USERNAME%');
		echo '"' . htmlspecialchars('http://my.bzflag.org/weblogin.php?action=weblogin&url=') . $url;
		echo '" class="login">Login</a></li>' . "\n";
		
		
		//writeLink('Login/', 'Login', (strcmp($name, 'Login') == 0));
	} else 
	{
		writeLink('Logout/', 'Logout', (strcmp($name, 'Logout') == 0));		
	}
	
?>
</ul>
<div id="infobar">
<?php
	$online_users_page_viewed = true;
	if (!(strcmp($name, 'Online') == 0))
	{
		$online_users_page_viewed = false;
	}
	
	echo '<div class="userbuttons">' . "\n";
	echo '<span class="date">' . date('Y-m-d H:i:s T') . '</span>';
	
	// list of servers
	echo ' <span class="Servers">Match servers: ';
	$query = ('SELECT SUM(`cur_players_total`) AS `cur_players_total`'
			  . ' FROM `servertracker`'
			  . ' ORDER BY `id`');
	if (!($result = $site->execute_silent_query('servertracker', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('Could not find out servername and serveraddress to be updated.');
	}
	while ($row = mysql_fetch_array($result))
	{
		if (intval($row['cur_players_total']) === 1)
		{
			echo '<a href="' . baseaddress() . 'Servertracker/">' . '1 player</a>';
		} else
		{
			echo '<a href="' . baseaddress() . 'Servertracker/">' . (strval($row['cur_players_total'])?strval($row['cur_players_total']):'0') . ' players</a>';
		}
	}
	mysql_free_result($result);
	
	// run the update script:
	// >/dev/null pipes output to nowhere
	// & lets the script run in the background
	//moved to cron
//	exec('php ' . dirname(__FILE__) . '/cli/servertracker_query_backend.php >/dev/null &');
	echo '</span>';
	
	if (!$online_users_page_viewed)
	{
		echo ' <span class="onlineUsers">Website: ';
		$query = 'SELECT count(`playerid`) AS `num_players` FROM `online_users`';
		if (!($result = @$site->execute_silent_query('online_users', $query, $connection)))
		{
			$site->dieAndEndPage('<p>Could not get number of online users from database.</p>');
		}
		
		$n_users = (mysql_fetch_array($result));
		if (intval($n_users['num_players']) === 1)
		{
			echo '<a href="' . baseaddress() . 'Online/' . '">' . '1 user</a>';
		} else
		{
			echo '<a href="' . baseaddress() . 'Online/' . '">' . $n_users['num_players'] . ' users</a>';
		}
		echo '</span>' . "\n";
		
		mysql_free_result($result);
		unset($n_users);
	}
	
	
	
	// close userbutton div
	echo "\n" . '</div>';
?>
</div>
</div>

<div class="content">
