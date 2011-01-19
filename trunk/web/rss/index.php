<?php
require realpath('../CMS/siteinfo.php');

function convert_datetime($str) {
	list($date, $time) = explode(' ', $str);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute, $second) = explode(':', $time);

	$ts = mktime($hour, $minute, $second, $month, $day, $year);
	return $ts;
}
	
$site = new siteinfo();
$connection = $site->connect_to_db();
	
$query = '(SELECT timestamp FROM matches) UNION (SELECT timestamp FROM news) ORDER BY timestamp DESC LIMIT 1';
$res = mysql_query($query);
$row = mysql_fetch_object($res);
$pubDate = date("r",  strtotime($row->timestamp)); 
$w3cDate = date("Y-m-d\TH:i:s+00:00", convert_datetime($row->timestamp));

	
$query = '(SELECT ' . sqlSafeStringQuotes('match') . ' as type, timestamp,team1_points,team2_points,t1.name team1_name,t2.name team2_name ,null as author, null as announcement';
$query .= ' FROM matches LEFT JOIN teams t1 ON matches.team1_teamid = t1.id LEFT JOIN teams t2 ON matches.team2_teamid = t2.id  ';
$query .= ' ORDER BY timestamp DESC LIMIT 0,10 ) UNION ';
$query .= '(SELECT ' . sqlSafeStringQuotes('news') . ', timestamp, 0, 0, null, null, author, announcement FROM news ';
$query .= 'ORDER BY timestamp DESC LIMIT 0,10 ) ORDER BY timestamp DESC LIMIT 10';


$res = mysql_query($query);
if (mysql_num_rows($res) >= 1) {
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
  <channel>
  <atom:link href="'.baseaddress().'rss/" rel="self" type="application/rss+xml" />
  <title>Ducati League - Latest News and Match Results</title>
      <link>'.baseaddress().'/</link>
      <description>Latest news and match results for Ducati League</description>
      <language>en</language>
      <copyright>GPL</copyright>
      <lastBuildDate>'.$pubDate.'</lastBuildDate>
      <generator>'.baseaddress().'rss/</generator>
      <webMaster>ducati@league.is.best.in.bz (DucCouncil)</webMaster>
      <ttl>60</ttl>
      <dc:creator>'.baseaddress().'</dc:creator>
      <dc:date>'.$w3cDate.'</dc:date>
      <sy:updatePeriod>hourly</sy:updatePeriod>
      <sy:updateFrequency>60</sy:updateFrequency>
      <sy:updateBase>2003-09-01T12:00+00:00</sy:updateBase>
';
	while ($row = mysql_fetch_object($res)) {
		$type = $row->type;
		$ts = convert_datetime($row->timestamp);
		$guid_ts =  strtotime($row->timestamp);
		$date1 = date("M d H:i", $ts);
		$date2 = date("r", $ts);
		
		if ($type === 'match') 
		{
			echo "<item>
			<guid>".baseaddress()."Matches/?$guid_ts</guid>
		    <title>$date1 => $row->team1_name: $row->team1_points, $row->team2_name: $row->team2_points</title>
		    <link>".baseaddress()."Matches/</link>
		    <description><![CDATA[Match result:<br/><b>$row->team1_points</b>  $row->team1_name <br/><b>$row->team2_points</b> $row->team2_name]]></description>
		    <pubDate>$date2</pubDate>
			</item>";
		} else
		{
			echo "<item>
			<guid>".baseaddress()."News/?$guid_ts</guid>
		    <title>$date1 => News by $row->author</title>
		    <link>".baseaddress()."News/</link>
		    <description><![CDATA[$row->announcement]]></description>
		    <pubDate>$date2</pubDate>
			</item>";
		}
	
	}
	echo '</channel></rss>';
}
else {
	header('Content-Type: text/html');
	echo 'Error: no matches and news found.';
}
?>