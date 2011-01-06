<?php
	require_once '../CMS/permissions.php';
			
	$pw = '';
	if (isset($_POST['pw']))
	{
		$pw = $_POST['pw'];
	}
	
	$loginname = '';
	if (isset($_POST['loginname']))
	{
		$loginname = $_POST['loginname'];
	}
	
	if (isset($_POST['pw']) && isset($_POST['loginname']))
	{
		// initialise permissions
		no_permissions();
		
		$correctUser = false;
		$correctPw = false;
		
		
		$lenLogin = strlen($loginname);
		if (($lenLogin > 50) || ($lenLogin < 1))
		{
			require_once '../CMS/navi.inc';
			echo '<div class="static_page_box">' . "\n";
			echo '<p class="first_p">User names must be using less than 50 but more than 0 <abbr title="characters">chars</abbr>.</p>' . "\n";
			$site->dieAndEndPage();
		}
		
		// get player id
		$query = 'SELECT `id`';
		if ($site->force_external_login_when_trying_local_login())
		{
			$query .= ', `external_playerid` ';
		}
		$query .= ' FROM `players` WHERE `name`=' . sqlSafeStringQuotes($loginname);
		// only one player tries to login so only fetch one entry, speeds up login a lot
		$query .= ' LIMIT 1';
		
		// execute query
		if (!($result = @$site->execute_query('players', $query, $connection)))
		{
			require_once '../CMS/navi.inc';
			echo '<div class="static_page_box">' . "\n";
			// query failed
			$site->dieAndEndPage(('Could not get id for name ' . sqlSafeString($loginname)));
		}
		
		
		// initialise with reserved player id 0 (no player)
		$playerid = (int) 0;
		$convert_to_external_login = true;
		while($row = mysql_fetch_array($result))
		{
			$playerid = $row['id'];
			if ($site->force_external_login_when_trying_local_login() && !(strcmp(($row['external_playerid']), '') === 0))
			{
				$convert_to_external_login = false;
			}
		}
		mysql_free_result($result);
		
		// local login tried but external login forced in settings
		if (!$convert_to_external_login && $site->force_external_login_when_trying_local_login())
		{
			$msg = '<span class="unread_messages">You already enabled ';
			if (isset($module['bzbb']) && ($module['bzbb']))
			{
				$url = urlencode(baseaddress() . 'Login/' . '?bzbbauth=%TOKEN%,%USERNAME%');
				$msg .= '<a href="' . htmlspecialchars('http://my.bzflag.org/weblogin.php?action=weblogin&url=') . $url;						
				$msg .= '">global (my.bzflag.org/bb/) login</a>';
			} else
			{
				$msg .= 'external logins';
			}
			$msg .= ' for this account.</span>' . "\n";
			die_with_no_login($msg);
		}
		
		
		if (intval($playerid) === 0)
		{
			die_with_no_login('The specified user is not registered. You may want to <a href="./">try logging in again</a>.');
		}
		
		// get password from database in order to compare it with the user entered password
		$query = 'SELECT `password`, `password_encoding` FROM `players_passwords` WHERE `playerid`=' . sqlSafeStringQuotes($playerid);
		// only one player tries to login so only fetch one entry, speeds up login a lot
		$query .= ' LIMIT 1';
		
		// execute query
		if (!($result = @$site->execute_query('players_passwords', $query, $connection)))
		{
			require_once '../CMS/navi.inc';
			echo '<div class="static_page_box">' . "\n";
			// query failed
			$site->dieAndEndPage(('Could not get password for player with id ' . sqlSafeString($playerid)));
		}
		
		// initialise without md5 (hash functions will cause collisions despite passwords will not match)
		$password_md5_encoded = false;
		// no password is default and will not match any password set
		$password_database = '';
		while($row = mysql_fetch_array($result))
		{
			if (strcmp($row['password_encoding'],'md5') === 0)
			{
				$password_md5_encoded = true;
			}
			$password_database = $row['password'];
		}
		mysql_free_result($result);
		
		$lenPw = strlen($pw);
		
		// webleague imported passwords have unknown length limitations 
		if (!$password_md5_encoded)
		{
			if (($lenPw < 10) || ($lenPw > 32))
			{
				require_once '../CMS/navi.inc';
				echo '<div class="static_page_box">' . "\n";
				echo ('<p class="first_p">Passwords must be using less than 32 but more than 9 <abbr title="characters">chars</abbr>.'
					  . ' You may want to <a href="./">try logging in again</a>.</p>' . "\n");
				$site->dieAndEndPage();
			}
		} else
		{
			// generate md5 hash of user entered password
			$pw = md5($pw);
		}
		
		if (!(strcmp($password_database, $pw) === 0))
		{
			// TODO: automatically log these cases and lock account for some hours after several unsuccessful tries
			require_once '../CMS/navi.inc';
			echo '<div class="static_page_box">' . "\n";
			echo '<p class="first_p">Your password does not match the stored password. You may want to <a href="./">try logging in again</a>.</p>' . "\n";
			$site->dieAndEndPage();
		}
		
		// sanity checks passed -> login successful
		
		// standard permissions for user
		$_SESSION['username'] = $loginname;
		$_SESSION['user_logged_in'] = true;
		$internal_login_id = $playerid;
		
		// permissions for private messages
		allow_add_messages();
		allow_delete_messages();
		
//		require_once '../CMS/navi.inc';
//		echo '<div class="static_page_box">' . "\n";
		
		// username and password did match but there might be circumstances
		// where the caller decides the login was not successful, though
	}
?>
