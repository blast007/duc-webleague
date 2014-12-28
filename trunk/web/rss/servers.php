<?php
	// this is plain text!
	header('Content-Type: text/plain');
	
	require realpath('../CMS/siteinfo.php');
	
	$site = new siteinfo();
	
	$connection = $site->connect_to_db();
	
	// display servers
	$query = ('SELECT * FROM `servertracker` ORDER BY type');
	if (!($result = @$site->execute_silent_query('servertracker,server_export', $query, $connection)))
	{
		$site->dieAndEndPage('It seems like the servers list can not be accessed for an unknown reason.');
	}
	while ($row = mysql_fetch_array($result))
	{
		switch ($row['type']) {
		 	case "match" 	: echo 'MS'; break;
		 	case "replay" 	: echo 'RS'; break;
			default		: echo 'PS'; break;
		}
		$serveraddr	= preg_split('/:/',$row['serveraddress']);
		echo ': ' . htmlent_decode($serveraddr[0]) . ', ' . htmlent_decode($serveraddr[1]) . ', ' . htmlent_decode($row['description']) . "\n";
	}
	mysql_free_result($result);
	
	
	
	// done with outputting stats
?>