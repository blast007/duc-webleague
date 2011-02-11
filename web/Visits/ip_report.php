<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	
	$display_page_title = 'Visits log';
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require realpath('../CMS/navi.inc');
	
	$site = new siteinfo();
	
	$connection = $site->connect_to_db();
	$randomkey_name = 'randomkey_user';
	$viewerid = (int) getUserID();
	
	$allow_view_user_visits = false;
	if (isset($_SESSION['allow_view_user_visits']))
	{
		if (($_SESSION['allow_view_user_visits']) === true)
		{
			$allow_view_user_visits = true;
		}
	}
	//get period in days
	$interval = 30;
	if (isset($_GET['visits_period']))
	{
		$i = (int) $_GET['visits_period'];
		if ($i > 0 ) $interval = $i;
	}

	
	
	
	echo '<h1 class="tools">Duplicated IPs log</h1>';
	
	// need an overview button to enable navigation within the page
	echo '<div class="simple-paging"><a href="./" class="button">Back to overview</a></div>' . "\n";
	
	
	echo '<div class="main-box">';
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p class="first_p">You need to login in order to view the visits log!</p>';
		$site->dieAndEndPageNoBox();
	}
	
	// only allow looking when having the permission
	if ($allow_view_user_visits === false)
	{
		$site->dieAndEndPageNoBox('You (id=' . sqlSafeString($viewerid) . ') have no permissions to view the visits log!');
	}
	
		
	
	// get list of double ips in last 30 days
	$query = 'SELECT COUNT(DISTINCT v.playerid) as num_players,v.`ip-address`, v.`host` '
	. ' FROM `visits` v '
	. ' WHERE v.timestamp > date_sub(' . sqlSafeStringQuotes(date('Y-m-d H:i:s')) . ',interval ' . $interval . ' day) '
	. ' GROUP BY `ip-address` HAVING num_players > 1';				

	
	if (!($result = @$site->execute_query('visits', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('<p>It seems like the ip report can not be accessed for an unknown reason.</p>');
	}
	
	echo "\n" . '<form enctype="application/x-www-form-urlencoded" method="get" action="" class=" simpleform">' . "\n";
	echo '<div class="formrow"><label for="visits_period">Period:</label> ' . "\n";
	echo '<span><select id="visits_period" name="visits_period">';
	echo '<option';
	if ($interval === 30)
	{
		echo ' selected="selected"';
	}
	echo ' value="30">30 days</option>';
	echo '<option';
	if ($interval === 60)
	{
		echo ' selected="selected"';
	}
	echo ' value="60">60 days</option>';
	echo '<option';
	if ($interval === 90)
	{
		echo ' selected="selected"';
	}
	echo ' value="90">90 days</option>';
	echo '<option';
	if ($interval === 180)
	{
		echo ' selected="selected"';
	}
	echo ' value="180">180 days</option>';
	echo '<option';
	if ($interval === 365)
	{
		echo ' selected="selected"';
	}
	echo ' value="365">1 year</option>';
	echo '</select></span>';
	echo '</div> ' . "\n";
	
	echo '<div style="display:inline">';
	$site->write_self_closing_tag('input type="submit" name="search" value="Search" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	
	$visits_list = array (array ());
	// read each entry, row by row
	$id = 0;
	while ($row = mysql_fetch_array($result))
	{
		$visits_list[$id]['ip-address'] = $row['ip-address'];
		$visits_list[$id]['host'] = $row['host'];
		$visits_list[$id]['players'] = '<ul class="visits_report">';
		
		$query = 'SELECT DISTINCT v.playerid, p.name '
		. ' FROM `visits` v LEFT JOIN players p ON (p.id = v.playerid)'
		. ' WHERE v.timestamp > date_sub( ' . sqlSafeStringQuotes(date('Y-m-d H:i:s')) . ',interval ' . $interval . ' day) '
		. ' AND v.`ip-address` = ' . sqlSafeStringQuotes($row['ip-address']);	
		if ($result_details = @$site->execute_query('visits', $query, $connection))
		{
			while ($row_details = mysql_fetch_array($result_details))
			{
				$visits_list[$id]['players'] .= '<li><a href="/Players/?profile=' . $row_details['playerid'] . '">' . $row_details['name'] . '</a></li>'; 
			}			
		} 
		$visits_list[$id]['players'] .= '</ul>';
				
		$id++;
	}
	
	// query result no longer needed
	mysql_free_result($result);
	
	if ($id === 0)
	{
		unset($id);
		$site->dieAndEndPageNoBox('There were no duplicated IPs in that period.');
	}
	
	// format the output with a nice table
	echo "\n" . '<table id="table_team_members" class="big">' . "\n";
	
	echo '<tr>' . "\n";
	echo '	<th>ip-address</th>' . "\n";
	echo '	<th>host</th>' . "\n";
	echo '	<th>Players</th>' . "\n";
	echo '	<th>Actions</th>' . "\n";
	echo '</tr>' . "\n\n";
	
	// walk through the array values
	foreach($visits_list as $visits_entry)
	{
		echo '<tr>' . "\n";
		echo '	<td>' . $visits_entry['ip-address'] . '</td>' . "\n";
		echo '	<td>' . $visits_entry['host'] . '</td>' . "\n";
		echo '	<td>' . $visits_entry['players'] . '</td>' . "\n";
		echo '	<td>';
		echo '	<a href="./?search_string=' . $visits_entry['ip-address'] . '&amp;search_type=ip-address&amp;search_result_amount=200&amp;search=Search" class="button">Details</a>';
		echo '  </td>' . "\n";
		echo '</tr>' . "\n";
	}
	echo '</table>' . "\n";
	unset($id);
	
	
?></div>
</div>
</body>
</html>
