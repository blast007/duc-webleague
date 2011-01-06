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
    
	echo '<h1 class="tools">Servers</h1>';
	
	echo '<div class="static_page_box">' . "\n";
	if (!($logged_in && (isset($_SESSION['allow_watch_servertracker'])) && ($_SESSION['allow_watch_servertracker'])))
	{
		echo '<p>You need to be logged in in order to view this page.</p>' . "\n";
		$site->dieAndEndPage();
	}
	$use_internal_db = true;
	
	require 'list.php';
	
	$connection = $object->loudless_pconnect_to_db();
	
	if (isset($_GET['server']))
	{
		echo '<p class="simple-paging"><a class="button previous" href="./">overview</a></p>' . "\n";
		$server = urldecode($_GET['server']);
		formatbzfquery_last($server, $connection);
	} else
	{
		echo '<h2>Match servers</h2>';
		
		formatbzfquery("dub.bzflag.net:59998", $connection);
		
		formatbzfquery("dub.bzflag.net:59999", $connection);
		
		formatbzfquery("quol.bzflag.bz:59998", $connection);
		
		formatbzfquery("studpups.bzflag.net:59998", $connection);
		
		formatbzfquery("brl.arpa.net:59998", $connection);
		
		formatbzfquery("brl.arpa.net:59999", $connection);
				
		echo '<h2>Public servers</h2>';
		
		formatbzfquery("dub.bzflag.net:5157", $connection);
		
		formatbzfquery("dub.bzflag.net:5154", $connection);
		
		formatbzfquery("quol.bzflag.bz:5162", $connection);
		
		formatbzfquery("studpups.bzflag.net:5156", $connection);
		
		formatbzfquery_last("brl.arpa.net:5157", $connection);
		
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