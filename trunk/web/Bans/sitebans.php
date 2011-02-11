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
	

	
	$allow_manage_sitebans = false;
	if (isset($_SESSION['allow_manage_sitebans']))
	{
		if (($_SESSION['allow_manage_sitebans']) === true)
		{
			$allow_manage_sitebans = true;
		}
	}
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0 || $allow_manage_sitebans === false)
	{
		echo '<p class="first_p">You have no permissions to view this page!</p>';
		$site->dieAndEndPageNoBox();
	}
	
	
	// Delete ban
	if (isset($_GET['delete']) && $allow_manage_sitebans)
	{
		$seasonid =  intval($_GET['delete']);
		$query = ('DELETE FROM `sitebans` WHERE `id` =' . sqlSafeStringQuotes($seasonid));
			
		if (!($result = $site->execute_query('sitebans', $query, $connection)))
		{
		
			$site->dieAndEndPage('Row could not be deleted due to a sql problem!');
		}		
	}
	//add a ban
	if (isset($_POST['create']) && $allow_manage_sitebans)
	{
		
		$ip_mask = $_POST['ip_mask'];
		$reason  = $_POST['reason'];
		if (!preg_match('/^([0-9]+)\.([0-9\.\*\/]*)$/',$ip_mask))
		{
			$site->dieAndEndPage('Wrong ip mask!');
			
		} else if (!$reason or $reason === '')
		{
			$site->dieAndEndPage('Reason is missing!');
			
		}else
		{
			$query = ('INSERT INTO `sitebans` (`timestamp`, `playerid` '
					  . ', `ip_mask`, `reason`)'
					  . ' VALUES (' . sqlSafeStringQuotes(date('Y-m-d H:i:s')) . ',' . sqlSafeStringQuotes($viewerid) . ',' . sqlSafeStringQuotes($ip_mask) 
					  . ', ' . sqlSafeStringQuotes($reason) . ')' );
			
			if (!($result = $site->execute_query('sitebans', $query, $connection)))
			{
				unlock_tables();
				$site->dieAndEndPage('The record could not be created due to a sql problem!');
			}
		}
	}
	
		
	echo '<div class="toolbar">';
	echo '</div>';
	
	echo '<h1 class="tools">Sitebans management</h1>';
	
	echo '<div class="main-box">';
	
	//form for new siteban
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="post" action="./sitebans.php" class="simpleform">' . "\n";
	
	// input string
	echo '<div class="formrow"><label for="ip_mask">IP mask:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="ip_mask" id="ip_mask" name="ip_mask" value=""');
	
	echo '</span></div> ' . "\n";
	
	echo '<div class="formrow"><label for="reason">Reason:</label> ' . "\n";
	
	echo '<span>';
		//by default is 45days length
		$site->write_self_closing_tag('input type="text" title="Reason" id="reason" name="reason" value=""' );
	
	echo '</span></div> ' . "\n";
	echo '<div class="formrow">';
	$site->write_self_closing_tag('input type="submit" name="create" value="Add a ban" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	
	
	$query = ('SELECT s.*, p.name as author FROM sitebans s LEFT JOIN players p' 
	. ' ON (p.id = s.playerid) ORDER BY ip_mask DESC');
			
	if (!($result = @$site->execute_query('sitebans', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of results could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	
	if ($rows === (int) 0)
	{
		echo '<p class="message">No sitebans in database.</p>' . "\n";
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	
	echo '<div class="main-box">';
	
	echo '<table id="banslist" class="big">' . "\n";
	echo '<thead><tr>' . "\n";
	echo '	<th>Date</th>' . "\n";
	echo '	<th>IP Mask</th>' . "\n";
	echo '	<th>Author</th>' . "\n";
	echo '	<th>Reason</th>' . "\n";
	echo '	<th>Actions</th>' . "\n";
	echo '</tr></thead>' . "\n\n";
	
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$results_list[$id]['timestamp'] = $row['timestamp'];
		$results_list[$id]['author'] = $row['author'];
		$results_list[$id]['reason'] = $row['reason'];
		$results_list[$id]['ip_mask'] = $row['ip_mask'];
		$results_list[$id]['id'] = $row['id'];		
	}
	unset($results_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
	
		
	// walk through the array values
	foreach($results_list as $result_entry)
	{
		echo '<tr>' . "\n";
		echo '<td>' . $result_entry['timestamp'] . '</td>' . "\n";
		echo '<td>' . $result_entry['ip_mask'] . '</td>' . "\n";		
		echo '<td>' . $result_entry['author'] . '</td>' . "\n";
		echo '<td>' . $result_entry['reason'] . '</td>' . "\n";
	
		// show allowed actions based on permissions
		if ($allow_manage_sitebans)
		{
			echo '<td>';
			echo '<a class="button" href="./sitebans.php?delete=' . htmlspecialchars($result_entry['id']) . '" 
			onclick="return confirm(\'Are you sure you want to delete this record?\')" >Delete record</a> ';
			echo '</td>' . "\n";
		}
		
		echo '</tr>' . "\n\n";
			}
	unset($results_list);
	unset($result_entry);
	
	
	// no more records to display
	echo '</table>' . "\n";
	

	
?>
	</div>
</div>
</body>
</html>