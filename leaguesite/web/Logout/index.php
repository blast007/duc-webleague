<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	require_once ('../CMS/siteinfo.php');	
	$site = new siteinfo();
	
	$connection = $site->connect_to_db();
	
	// find out if table exists
	$query = 'SHOW TABLES LIKE ' . "'" . 'online_users' . "'";
	$result = @mysql_query($query, $connection);
	$rows = @mysql_num_rows($result);
	// done
	mysql_free_result($result);
	
	$onlineUsers = false;
	if ($rows > 0)
	{
		// no need to create table in case it does not exist
		// any interested viewer looking at the online page will create it
		$onlineUsers = true;
	}
	
	// buffer possible output
	$buffer = '';
	ob_start();
	// use the resulting data
	if ($onlineUsers && (getUserID() > 0))
	{
		$query = 'DELETE FROM `online_users` WHERE playerid=' . sqlSafeStringQuotes(getUserID());
		@$site->execute_query('online_users', $query, $connection);
	}
	// stop buffering
	$buffer .= ob_get_contents();
	ob_end_clean();
	
	// we're done with the database for now
	@mysql_close($connection);
	
	// reset the variables for the user
	session_unset();
	//destroy the session
	session_destroy();

	
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require '../CMS/navi.inc';
	
	echo '<div class="static_page_box">' . "\n";
	// write buffer
	echo $buffer;
?>
<p class="first_p">You have been logged out.</p>
</div>
</div>
</body>
</html>