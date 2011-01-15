<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require ('../CMS/navi.inc');
	
	$connection = $site->connect_to_db();
	$viewerid = (int) getUserID();
	
	$allow_moderate_shoutbox = false;
	if (isset($_SESSION['allow_moderate_shoutbox']))
	{
		if (($_SESSION['allow_moderate_shoutbox']) === true)
		{
			$allow_moderate_shoutbox = true;
		}
	}
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p class="first_p">You need to log in for accessing shoutbox archive!</p>';
		$site->dieAndEndPageNoBox();
	}
	
	
	
	echo '<h1 class="tools">Shoutbox archive</h1>';
	
		
	echo '<div class="toolbar">';
	
	// only allow looking when having the permission
	if ($allow_moderate_shoutbox === true)
	{
		echo '<a href="/Shoutbox/" class="button">Shoutbox management</a>';
	}
	
	
	echo '</div>';
	
	
	
	
	$query = ('SELECT * FROM wtagshoutbox ORDER BY date ASC LIMIT 0,500');
			
	if (!($result = @$site->execute_query('wtagshoutbox', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of shouts results could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	
	if ($rows === (int) 0)
	{
		echo '<p class="message">No messages in shoutbox.</p>' . "\n";
		setTableUnchanged($site, $connection);
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	
	echo '<div class="main-box" id="chat">';
		
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		echo '<div class="user">' . "\n";
		echo '<span class="date">' . $row['date'] . '</span>';
		echo '<span class="name">' . $row['name'] . ':</span>';
		echo '<span class="text">' . $row['message'] . '</span></div>' . "\n";
	}
	
	
	// query result no longer needed
	mysql_free_result($result);
	
	

	
?>
	</div>
</div>
</body>
</html>