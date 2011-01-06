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
		echo '<p class="first_p">You need to login in order to manage the shoudbox!</p>';
		$site->dieAndEndPageNoBox();
	}
	
	// only allow looking when having the permission
	if ($allow_moderate_shoutbox === false)
	{
		$site->dieAndEndPageNoBox('You (id=' . sqlSafeString($viewerid) . ') have no permissions to manage the shoutbox!');
	}
	
	
	//Season deactivation
	if (isset($_GET['delete']) && $allow_moderate_shoutbox)
	{
		$seasonid =  intval($_GET['delete']);
		$query = ('DELETE FROM `wtagshoutbox` WHERE `messageid` =' . sqlSafeStringQuotes($seasonid));
			
		if (!($result = $site->execute_query('wtagshoutbox', $query, $connection)))
		{
		
			$site->dieAndEndPage('The message could not be deleted due to a sql problem!');
		}		
	}
	
	
	
		
	echo '<div class="toolbar">';
	echo '</div>';
	
	echo '<h1 class="tools">Shoutbox management</h1>';
	
	
	$query = ('SELECT * FROM wtagshoutbox ORDER BY date DESC LIMIT 0,100');
			
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
	
	
	echo '<div class="main-box">';
	
	echo '<table id="shouts" class="big">' . "\n";
	echo '<thead><tr>' . "\n";
	echo '	<th>Date</th>' . "\n";
	echo '	<th>Name</th>' . "\n";
	echo '	<th>Message</th>' . "\n";
	echo '	<th>Actions</th>' . "\n";
	echo '</tr></thead>' . "\n\n";
	
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['messageid'];
		$results_list[$id]['date'] = $row['date'];
		$results_list[$id]['name'] = $row['name'];
		$results_list[$id]['message'] = $row['message'];
		$results_list[$id]['id'] = $row['messageid'];		
	}
	unset($results_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
	
		
	// walk through the array values
	foreach($results_list as $result_entry)
	{
		echo '<tr>' . "\n";
		echo '<td>' . $result_entry['date'] . '</td>' . "\n";
		echo '<td>' . $result_entry['name'] . '</td>' . "\n";
		echo '<td>' . $result_entry['message'] . '</td>' . "\n";
	
		// show allowed actions based on permissions
		if ($allow_moderate_shoutbox)
		{
			echo '<td>';
			echo '<a class="button" href="./?delete=' . htmlspecialchars($result_entry['id']) . '" 
			onclick="return confirm(\'Are you sure you want to delete this message?\')" >Delete message</a> ';
			echo '</td>' . "\n";
		}
		
		echo '</tr>' . "\n\n";
			}
	unset($results_list);
	unset($result_entry);
	
	
	// no more matches to display
	echo '</table>' . "\n";
	

	
?>
	</div>
</div>
</body>
</html>