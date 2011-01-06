<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require '../CMS/navi.inc';
	
	if (!isset($site))
	{
		$site = new siteinfo();
	}
	
	function showTimeSince($gettime) {
		$rtn = "";
        $gettime = time() - $gettime;
        $d = floor($gettime / (24 * 3600));
        $gettime = $gettime - ($d * (24 * 3600));
        $h = floor($gettime / (3600));
        $gettime = $gettime - ($h * (3600));
        $m = floor($gettime / (60));
        $gettime = $gettime - ($m * 60);
        $s = $gettime;
		$rtn = '';
        if ($d != 0) $rtn .= $d.'d ';
        if ($h != 0) $rtn .= $h.'h ';
        if ($m != 0) $rtn .= $m.'m ';
        if ($s != 0) $rtn .= $s.'s';
		if ($rtn == '') $rtn = '0s';
        return $rtn;
	}
	
	function convert_datetime($str) {
		list($date, $time) = explode(' ', $str);
		list($year, $month, $day) = explode('-', $date);
		list($hour, $minute, $second) = explode(':', $time);

		$ts = mktime($hour, $minute, $second, $month, $day, $year);
		return $ts;
	}
	
	echo '<div class="static_page_box">' . "\n";
	
	$connection = $site->connect_to_db();
	$table_name = 'online_users';
	
	$onlineUsers = false;
	$query = 'SELECT * FROM `' . sqlSafeString($table_name) . '` ORDER BY last_activity DESC';	
	if ($result = (@$site->execute_query($table_name, $query, $connection)))
	{
		$onlineUsers = true;
	} else
	{
		$onlineUsers = false;
		mysql_free_result($result);
	}
	
	// use the resulting data
	if ($result)
	{
		$rows = mysql_num_rows($result);
		// by definition this is a joke but online guests are not shown by default
		if ($rows < 1)
		{
			echo '<div class="online_user">There are currently no users logged in.</div>';
		} else
		{
			echo '<table class="online_user">';
			while ($row = mysql_fetch_object($result)) {
				echo '<tr><td><a href="'.basepath().'Players/?profile='.$row->playerid.'">'.htmlentities($row->username).'</a></td><td>';
				echo '(idle: '.showTimeSince(convert_datetime($row->last_activity)).')</td></tr>';
			}
			echo '</table>';
		}
		mysql_free_result($result);
	}
	mysql_close($connection);
?>

</div>
</div>
</body>
</html>
