<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set ('allow_url_fopen', 0);
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	require_once '../CMS/siteinfo.php';
	$site = new siteinfo();
	
	if ($site->use_xtml())
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\n";
		echo '     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	} else
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"' . "\n";
		echo '        "http://www.w3.org/TR/html4/strict.dtd">';
	}
	echo "\n" . '<html';
	if ($site->use_xtml())
	{
		echo ' xmlns="http://www.w3.org/1999/xhtml"';
	}
	echo '>' . "\n";
	echo '<head>' . "\n";
	echo '	' . ($site->write_self_closing_tag('meta content="text/html; charset=utf-8" http-equiv="content-type"'));
	
	require '../stylesheet.inc';
	
	// display favicon, if specified
	if (!(strcmp($site->favicon_path(), '') === 0))
	{
		echo '	';
		echo $site->write_self_closing_tag('link rel="icon" type="image/png" href="' . $site->favicon_path() . '"');
	}
	
	echo '<link rel="alternate" type="application/rss+xml" title="Ducati League" href="/rss/" />';
	
	
	$site->write_self_closing_tag('link rel="stylesheet" media="all" href="players.css" type="text/css"');
	// perhaps exclude email string, depending on browser
	$object = new siteinfo();
	if ($object->mobile_version())
	{
		// mobile browser
		echo '<style type="text/css">*.mehl { display: none; } table.punkte { left: 25em; }</style>';
	}
	echo '  <title>Official match servers</title>' . "\n";
	echo '</head>' . "\n";
	echo '<body>' . "\n";
	
	require '../CMS/navi.inc';
    
	
	$allow_manage_servers = false;
	if (isset($_SESSION['allow_manage_servers']))
	{
		if (($_SESSION['allow_manage_servers']) === true)
		{
			$allow_manage_servers = true;
		}
	}
	
	echo '<h1 class="tools">Servers</h1>';
	$viewerid = (int) getUserID();
	if ($allow_manage_servers && $viewerid)
	{
		echo '<div class="toolbar"> <a href="edit.php" class="button">Manage servers list</a></div>';	
	}
	
	
	
	echo '<div class="static_page_box">' . "\n";
	if (!($logged_in && (isset($_SESSION['allow_watch_servertracker'])) && ($_SESSION['allow_watch_servertracker'])))
	{
		echo '<p>You need to be logged in in order to view this page.</p>' . "\n";
		$site->dieAndEndPage();
	}
	$use_internal_db = true;
	
	require 'list.php';
	
	$connection = $site->connect_to_db();
	if (isset($_GET['server']))
	{
		echo '<p class="simple-paging"><a class="button previous" href="./">overview</a></p>' . "\n";
		$server = urldecode($_GET['server']);
		formatbzfquery_last($server, $connection);
	} else
	{
		echo '<h2>Match servers</h2>';
		
		$query = ('SELECT `id`, `servername`, `serveraddress`, `description` FROM `servertracker`'
				  . ' WHERE `type` = \'match\' ORDER BY `serveraddress`');
		if (!($result = $site->execute_query('servertracker', $query, $connection)))
		{
			die('Error during getting servers list.');
		}
		
		$last = mysql_num_rows($result);
		
		if ($last == 0)
		{
			echo '<p>No servers found.</p>';			
		}
		$i = 1;
		while ($row = mysql_fetch_array($result))
		{
			if ($i == $last)
			{
				formatbzfquery_last($row['serveraddress'], $connection,$row['description']);
			} else 
			{
				formatbzfquery($row['serveraddress'], $connection,$row['description']);
			}
			$i++;
		}
			
		echo '<h2>Public servers</h2>';
		
		$query = ('SELECT `id`, `servername`, `serveraddress`, `description` FROM `servertracker`'
				  . ' WHERE `type` = \'public\' ORDER BY `serveraddress`');
		if (!($result = $site->execute_query('servertracker', $query, $connection)))
		{
			die('Error during getting servers list.');
		}
		$last = mysql_num_rows($result);
		if ($last == 0)
		{
			echo '<p>No servers found.</p>';			
		}
		$i = 1;
		while ($row = mysql_fetch_array($result))
		{
			if ($i == $last)
			{
				formatbzfquery_last($row['serveraddress'], $connection,$row['description']);
			} else 
			{
				formatbzfquery($row['serveraddress'], $connection,$row['description']);
			}
			$i++;
		}
		
		
		echo '<h2>Replay servers</h2>';
		
		$query = ('SELECT `id`, `servername`, `serveraddress`, `description` FROM `servertracker`'
				  . ' WHERE `type` = \'replay\' ORDER BY `serveraddress`');
		if (!($result = $site->execute_query('servertracker', $query, $connection)))
		{
			die('Error during getting servers list.');
		}
		$last = mysql_num_rows($result);
		if ($last == 0)
		{
			echo '<p>No servers found.</p>';			
		}
		$i = 1;
		while ($row = mysql_fetch_array($result))
		{
			if ($i == $last)
			{
				formatshowserver_last($row['serveraddress'],$row['description']);
			} else 
			{
				formatshowserver($row['serveraddress'], $row['description']);
			}
			$i++;
		}
		
	}
?>
</div>
</div>
</body>
</html><?php
	// GU-spezifisch!
	//require_once '../../../CMS/siteinfo.php';
	//
	//$object = new siteinfo();
	@mysql_close($connection);
	unset($connection);
	$connection = $object->loudless_connect_to_db();
	if (!$connection)
	{
		// HTML oben schon beendet
		die('no connection to database');
	}
	
	if (!$use_internal_db)
	{
		// only execute once per day to avoid overhead
		date_default_timezone_set($site->used_timezone());
		
		$heute = date("d.m.y");
		$file = 'maintenance.txt';
		
		if (is_writable($file)) {
			
			// we open $filename in "attachemt" - mode.
			// pointer is at end of the file
			// there $somecontent will be saved later using fwrite()
			if (!$handle = fopen($file, "r")) {
				print "Can not open file $file";
				exit;
			}
		} else {
			print "File $file is not writeable";
		}
		
		// read first 10 chars
		$text = fread($handle, 10);
		
		// is DB info current?
		if (strcasecmp($text, $heute) == 0)
		{
			// nothing to do
			die();
		}
		
		// select database
		mysql_select_db("playerlist", $connection);
		
		// delete database data if information not current
		// expensive operation
		$query = 'TRUNCATE teams';
		$result = mysql_query($query, $connection);
		if (!$result)
		{
			print mysql_error();
			die("<br>\nQuery $query is not valid SQL.");
		}
		$query = 'TRUNCATE players';
		$result = mysql_query($query, $connection);
		if (!$result)
		{
			print mysql_error();
			die("<br>\nQuery $query is not valid SQL.");
		}
		
		$row = 1; // number of rows
		// create a new cURL resource
		$ch = curl_init();
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, 'http://league.bzflag.net/rss/export2.php');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// grab URL and pass it to the browser
		$output = curl_exec($ch);
		
		// close cURL resource, and free up system resources
		curl_close($ch);
		$handleRSS = $output;
		
		//	$handleRSS = fopen ("http://gu.bzleague.com/rss/export2.php","r"); // Datei zum Lesen oeffnen
		//	if (!$handle)
		//	{
		//		die('Could not connect to league website');
		//	}
		
		preg_match_all('/(TE:(.*))|(PL:(.*))/', $handleRSS, $handleRSS);
		$handleRSS = $handleRSS[0];
		//    $handleRSS = str_getcsv ($handleRSS, "\n");
		//    print_r($handleRSS);
		
		//	while ( ($data = str_getcsv ($handleRSS, ",")) !== FALSE )
		foreach ($handleRSS as $dataRow)
		{ // Daten werden aus der Datei in ein Feld $data gelesen
			
			//        preg_match_all('/((.*),(.*))|((.*),(.*),(.*))/' , $dataRow, $data);
			$data = explode(',', $dataRow, 4);
			//        $data = $data[0];
			//        $data = str_getcsv ($dataRow, ',');
			
			
			
			$num = count ($data); // Felder im Array $data werden gezaehlt
			$row++; // increment number of arrays
			
			if ($num == 2)
			{
				$data[1] = ltrim($data[1]);
			}
			if ($num > 2)
			{
				$data[2] = ltrim($data[2]);
			}
			//		print_r($data);
			
			// teams
			if ($num == 2)
			{
				$teamid = mysql_real_escape_string((int) (str_replace('TE: ', '', $data[0])));
				$name = '"' . mysql_real_escape_string(htmlentities($data[1])) . '"';
				
				$query = 'INSERT INTO teams (teamid, name) Values(' . $teamid . ',' . $name . ')';
				$result = mysql_query($query, $connection);
				
				if (!$result)
				{
					print mysql_error();
					die("<br>\nQuery $query is not valid SQL.");
				}
			}
			
			// players
			if ($num == 3)
			{
				$teamid = '"' . mysql_real_escape_string((int) (str_replace('PL: ', '', $data[0]))) . '"';
				
				$name = '"' . mysql_real_escape_string(htmlentities($data[2])) . '"';
				
				$query = 'INSERT INTO players (teamid, name) Values(' . $teamid . ', ' . $name . ')';
				$result = mysql_query($query, $connection);
				
				if (!$result)
				{
					print mysql_error();
					die("<br>\nQuery $query is not valid SQL.");
				}
			}
		}
		
		if (!(strcasecmp($text, $heute) == 0))
		{
			// delete content
			if (!fclose($handle)) {
				print "Can not close file $file";
				exit;
			}
			if (!$handle = fopen($file, 'w')) {
				print "Can not open file $file";
				exit;
			}
			if (!fwrite($handle, $heute)) {
				print "Can not write to file $file";
				exit;
			}
			@fclose($handle);
		}
	}
	//	fclose ($handleRSS);
?>