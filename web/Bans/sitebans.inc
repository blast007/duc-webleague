<?php
	
	$connection = $site->connect_to_db();	
	$query = ('SELECT ip_mask FROM `sitebans`' );
	$banned_user = false;
		
	if (!($result = $site->execute_query('sitebans', $query, $connection)))
	{
			$site->dieAndEndPage('The page could not be created due to a sql problem!');
	}	
	
	$host_addr = $_SERVER['REMOTE_ADDR'];
	$hostArr = preg_split('/\./',$host_addr);		
	
	$ip_match = false;
	while ($row = mysql_fetch_array($result))
	{
		$ip_mask = $row['ip_mask'];	
		$ipArr = preg_split("/\./",$ip_mask);		
		
		
		$ip_part_match = true;
		for($i=0;$i<4;$i++ )
		{
			//check if ip parts are same or is * stored here		
			if (!($hostArr[$i] === $ipArr[$i]) && !($ipArr[$i] === '*')) 
			{															
				//if no, then whoe IP doesn't match.
				$ip_part_match = false;
				break;
			}
			
		}
		$ip_match = $ip_part_match;
		if ($ip_match) break;
	
	}

	if ($ip_match)
	{
		// reset the variables for the user
		session_unset();
		//destroy the session
		session_destroy();		
		
		$banned_user=true;
				
	}
	
?>