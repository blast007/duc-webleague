<div class="home-right">
	
	<?php require_once(dirname(__FILE__) .'/Polls/showpoll.php'); ?>
	
	<div id="latest-news">
		<h2 class="news"><span>Latest news</span></h2>
		<div class="main-box article">
			<?php
				display_last_news(1);
			?>
		</div>
	</div>
	

	<div class="tabs">	
		<ul class="tabNavigation">
			<li><a href="#last-matches" class="matches">Last matches</a></li>
			<li><a href="#top-teams" class="teams">Standings</a></li>
			<li><a href="#new-players" class="players">New players</a></li>		
		</ul>
		
		<div id="last-matches" class="slide">
			<div class="main-box">
			<?php 	
				display_last_matches(5);
			?>			
			</div>
		</div>
		
		<div id="top-teams" class="slide">
			<div class="main-box">
				<?php 	
					display_top_teams(5);
				?>	
			</div>
		</div>
		
		<div id="new-players" class="slide">
			<div class="main-box">
				<?php 	
					display_new_players(5);
				?>	
			</div>
		</div>
	</div> 
	
	<div id="latest-activity">
		<h2 class="news"><span>Activity</span></h2>
		<div class="main-box">
			<?php
				display_activity(12,5);
			?>
		</div>
	</div>
	
	<!-- END of slideshow box
	plugin source: http://www.kevinresol.com/divslideshow/example.php
	-->	
 	<script type="text/javascript" charset="utf-8">
        $(function () {
            var tabContainers = $('div.tabs > div');
            tabContainers.hide().filter(':first').show();
                        
            $('div.tabs ul.tabNavigation a').click(function () {
                  tabContainers.hide();
                  tabContainers.filter(this.hash).show();
                  $('div.tabs ul.tabNavigation a').removeClass('selected');
                  $(this).addClass('selected');
                  return false;
            }).filter(':first').click();
        });

                
    if(jQuery('a#moreD').css('font-size')!='0px' && 
	jQuery('#article_body').height()>195){
		jQuery('#article_body').wrap('<div id="article"/>');
		jQuery('#moreD').show().toggle(function(){
			jQuery(this).text('show less ^');
			jQuery('#article').animate({
				'height':jQuery('#article_body').height()+20
			},100);
		},function(){
			jQuery('#article').animate({
				'height':195
			},100);
			jQuery(this).text('show more v');
		});
	}

	  
	  </script>
	
	
	
		 
</div>
<div class="footer">Webleague engine by <a href="/Players/?profile=1996">ts</a>; Improvements &amp; ducati layout by <a href="/Players/?profile=5343">osta</a>; 
Tank drawing by <a href="http://www.newgrounds.com/art/view/underarock/08-tank">UnderARock</a>; 
Shoutbox script - <a href="http://spacegirlpippa.co.uk" title="A free mini chat (shoutbox) script"> wTag </a>
</div>

<?php 

function display_last_news($limit)
{
	global $site;
	global $connection;
	
	$table_name = 'news';	
	$query = 'SELECT * FROM `' . $table_name . '` ORDER BY id DESC LIMIT 0, ' . $limit;
			
	$result = ($site->execute_query($table_name, $query, $connection));
	if (!$result)
	{
		$site->dieAndEndPage();
	}
	
	$rows = (int) mysql_num_rows($result);
	if ($rows === 0)
	{
		echo '<p class="first_p">No entries made yet.</p>' . "\n";
		$site->dieAndEndPage();
	}
	// more than wished announcements per page available in total
	unset($rows);
	
	$current_row = 0;
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		echo '<div class="article_header">' . "\n";
		echo '<div class="timestamp">';
		printf("%s", htmlentities($row["timestamp"]));
		echo '</div>' . "\n";
		echo '<div class="author">';
		echo 'By: ';
		$author = $site->forced_author($table_name);
		if (!(strcmp($author, '') === 0))
		{
			echo $author;
			// show the author to the ones who can add, edit or delete entries
			if ((isset($_SESSION[$entry_add_permission]) && ($_SESSION[$entry_add_permission]))
				|| ((isset($_SESSION[$entry_edit_permission])) && ($_SESSION[$entry_edit_permission]))
				|| ((isset($_SESSION[$entry_delete_permission])) && ($_SESSION[$entry_delete_permission])))
			{
				echo ' (' . $row["author"] . ')';
			}
				
		} else
		{
			echo $row["author"];
		}
		echo '</div>' . "\n";
		echo '</div>' . "\n";
		echo '<div id="article_body">';
		echo $row['announcement'];
		echo '</div>' . "\n";
		echo '<a href="javascript:void(0);" id="moreD" >show more v</a>';
		echo '<p class="simple-paging p0"><a href="/News/" class="button next">More news</a></p>';
	}
}



function display_activity($months, $topteams)
{
	global $site;
	global $connection;
		
		$query_overall = 'SELECT MONTH(timestamp) as month, YEAR(timestamp) as year,  count(id) as match_number'
		. ' FROM matches WHERE PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"),DATE_FORMAT(timestamp,"%Y%m")) < ' . $months
		. ' GROUP BY month, year ORDER BY year asc, month asc';
		
		$query_topteams = 'SELECT t.id, count(m.id) as match_number '
		. ' FROM teams t LEFT JOIN matches m ON (m.team1_teamid = t.id OR m.team2_teamid = t.id) '
		. ' WHERE PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"),DATE_FORMAT(timestamp,"%Y%m")) < ' . $months
		. ' GROUP BY t.id order by match_number DESC limit ' . $topteams;
		
		
		if (!($result = @$site->execute_query('matches', $query_overall, $connection)))
		{
			$site->dieAndEndPage('Could not find matches played.');
		}
		
		$n_rows = (int) mysql_num_rows($result);
		if ($n_rows < (int) 1)
		{
			mysql_free_result($result);
			$site->dieAndEndPage();
		}
		$match_stats = array();
		$i = 0;
		$chart_periods = '';
		$chart_values = '';
		$startyear = 2000;
		while ($row = mysql_fetch_array($result))
		{
			$match_stats[$i]['period'] = $row['month'] . '/' . $row['year'];
			$match_stats[$i]['match_number'] = $row['match_number'];
			$match_stats[$i]['month_number'] = (12 * ($row['year']-$startyear)) + $row['month']; 			
			if ($i != 0)
			{
				//if some team miss few months of activity
				if ($match_stats[$i]['month_number'] > ($match_stats[$i-1]['month_number'] + 1) )
				{
					for ($j=1; $j < ($match_stats[$i]['month_number'] - ($match_stats[$i-1]['month_number'])); $j++)
					{
						$chart_periods .= ",'" . getMonth($match_stats[$i-1]['month_number'] + $j, $startyear) . "'";
						$chart_values .= ",0";
					}
				} 								
				$chart_periods .= ',';
				$chart_values .= ',';
			}
			$chart_periods .= "'" . $row['month'] . (($row['month'] === '6')? '<br/>' . $row['year']:'')  . "'";
			$chart_values .=  $match_stats[$i]['match_number'] ;
			
			$i++;
		}		
		echo '<div id="chart-container-1"></div>' . "\n";
		echo '<p class="simple-paging p0"><a href="/Teams/stats.php" class="button next">More</a></p>';	
	?>
	<script src="/js/highcharts.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/themes/gray.js"></script>
	<script type="text/javascript">
	var chart1; // globally available
	$(document).ready(function() {
	      chart1 = new Highcharts.Chart({
	         chart: {
	            renderTo: 'chart-container-1',
	            defaultSeriesType: 'line',
	            height:  '200',
	         },
	         title: {
	            text: ''
	         },
	         xAxis: {
					categories: [<?php echo $chart_periods; ?>]
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Amount of matches'
				}
			},
			legend: {
				enabled: false
			},
			tooltip: {
				formatter: function() {
					return '<b>'+ this.y + '</b>';
				}
			},
			plotOptions: {
				series: {
					stacking: 'normal'
				}
			},
		    series: [{ title: '',
				data: [<?php echo $chart_values; ?>]
			}]
	      });
	   });	
	</script>
	<?php 
}


function display_top_teams($limit) 
{
	global $site;
	global $connection;
	
		$table_name = 'teams_overview';	
	$query = 'SELECT * FROM `teams` t LEFT JOIN teams_overview tov ON tov.teamid = t.id ' 
	. ' WHERE deleted = 1 AND SUBSTRING(activity, 1, 4) != \'0.00\' ORDER BY tov.score DESC LIMIT 0,' . $limit;
			
	$result = ($site->execute_query($table_name, $query, $connection));
	if (!$result)
	{
		$site->dieAndEndPage();
	}
	
	$rows = (int) mysql_num_rows($result);
	if ($rows === 0)
	{
		echo '<p class="first_p">No entries made yet.</p>' . "\n";
		$site->dieAndEndPage();
	}
	// more than wished announcements per page available in total
	unset($rows);
	
	echo '<table class="teams-list">' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Pos.</th>' . "\n";
	echo '	<th>Team</th>' . "\n";
	echo '	<th>Rating</th>' . "\n";
	echo '</tr>' . "\n\n";
	
	
	$current_row = 1;
	// read each entry, row by row
	include 'Seasons/seasons.inc';
	while ($row = mysql_fetch_array($result))
	{
		echo '<tr>' . "\n";
		echo '<td>' . ($current_row++) . '. </td>';
		echo '<td><a href="/Teams/?profile=' . $row['teamid'] . '">' . $row['name'] . '</a></td>' ;
		echo '<td>';
			rankingLogo($row['score']);
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
				
		
	}
	echo '</table>' . "\n";
	echo '<p class="simple-paging p0"><a href="/Teams/" class="button next">More</a></p>';
	
}


function display_new_players($limit) 
{
	global $site;
	global $connection;
	
	$table_name = 'players';	
	$query = 'SELECT * FROM `players` p LEFT JOIN players_profile pp ON pp.playerid = p.id WHERE status = "active" ORDER BY joined DESC LIMIT 0,' . $limit;
			
	$result = ($site->execute_query($table_name, $query, $connection));
	if (!$result)
	{
		$site->dieAndEndPage();
	}
	
	$rows = (int) mysql_num_rows($result);
	if ($rows === 0)
	{
		echo '<p class="first_p">No entries made yet.</p>' . "\n";
		$site->dieAndEndPage();
	}
	// more than wished announcements per page available in total
	unset($rows);
	
	echo '<table class="players-list">' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Name</th>' . "\n";
	echo '	<th>Joined</th>' . "\n";
	echo '</tr>' . "\n\n";
	
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		echo '<tr>' . "\n";
		echo '<td><a href="/Players/?profile=' . $row['playerid'] . '">' . $row['name'] . '</a></td>' ;
		echo '<td>';
		echo ago($row['joined']);
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
				
		
	}
	echo '</table>' . "\n";
	echo '<p class="simple-paging p0"><a href="/Players/" class="button next">More</a></p>';
	
}


function display_last_matches($limit) 
{
	global $site;
	global $connection;
	
	$table_name = 'matches';	
	$query = 'SELECT m.*, t1.name as team1_name, t2.name as team2_name FROM `matches` m '
	. ' LEFT JOIN teams t1 ON (m.team1_teamid = t1.id)' 
	. ' LEFT JOIN teams t2 ON (m.team2_teamid = t2.id)' 
	. ' ORDER BY timestamp desc LIMIT 0, ' . $limit;
			
	$result = ($site->execute_query($table_name, $query, $connection));
	if (!$result)
	{
		$site->dieAndEndPage();
	}
	
	$rows = (int) mysql_num_rows($result);
	if ($rows === 0)
	{
		echo '<p class="first_p">No entries made yet.</p>' . "\n";
		$site->dieAndEndPage();
	}
	// more than wished announcements per page available in total
	unset($rows);
	
	echo '<table class="matches-list">' . "\n";
	echo '<tr>' . "\n";
	echo '	<th>Time</th>' . "\n";
	echo '	<th>Teams</th>' . "\n";
	echo '	<th>Result</th>' . "\n";	
	echo '</tr>' . "\n\n";
	
	
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		echo '<tr>' . "\n";
		echo '<td>' . ago($row['timestamp']) . '</td>';
		echo '<td>' ;
		if ($row['team1_points'] >= $row['team2_points'])
		{
				team_name_from_id($row['team1_teamid'], $row['team1_name']);
				echo ' - ';
				team_name_from_id($row['team2_teamid'], $row['team2_name']);
			echo '</td><td>';
				echo htmlentities($row['team1_points']);
				echo ' - ';
				echo htmlentities($row['team2_points']);
		} else
		{
				team_name_from_id($row['team2_teamid'], $row['team2_name']);
				echo ' - ';
				team_name_from_id($row['team1_teamid'], $row['team1_name']);
			echo '</td><td>';
				echo htmlentities($row['team2_points']);
				echo ' - ';
				echo htmlentities($row['team1_points']);
		}
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
				
		
	}
	echo '</table>' . "\n";
	echo '<p class="simple-paging p0"><a href="/Matches/" class="button next">More</a></p>';
	
	
	
	
}



function team_name_from_id($id, $name)
	{
		echo '<a href="../Teams/?profile=' . ((int) $id) . '">' . $name . '</a>';
	}

function ago($datefrom,$dateto=-1)
    {
        // Defaults and assume if 0 is passed in that
        // its an error rather than the epoch
   
        if($datefrom==0) { return "A long time ago"; }
        if($dateto==-1) { $dateto = time(); }
       
        // Make the entered date into Unix timestamp from MySQL datetime field

        $datefrom = strtotime($datefrom);
   
        // Calculate the difference in seconds betweeen
        // the two timestamps

        $difference = $dateto - $datefrom;

        // Based on the interval, determine the
        // number of units between the two dates
        // From this point on, you would be hard
        // pushed telling the difference between
        // this function and DateDiff. If the $datediff
        // returned is 1, be sure to return the singular
        // of the unit, e.g. 'day' rather 'days'
   
        switch(true)
        {
            // If difference is less than 60 seconds,
            // seconds is a good interval of choice
            case(strtotime('-1 min', $dateto) < $datefrom):
                $datediff = $difference;
                $res = ($datediff==1) ? $datediff.' second ago' : $datediff.' seconds ago';
                break;
            // If difference is between 60 seconds and
            // 60 minutes, minutes is a good interval
            case(strtotime('-1 hour', $dateto) < $datefrom):
                $datediff = floor($difference / 60);
                $res = ($datediff==1) ? $datediff.' minute ago' : $datediff.' minutes ago';
                break;
            // If difference is between 1 hour and 24 hours
            // hours is a good interval
            case(strtotime('-1 day', $dateto) < $datefrom):
                $datediff = floor($difference / 60 / 60);
                $res = ($datediff==1) ? $datediff.' hour ago' : $datediff.' hours ago';
                break;
            // If difference is between 1 day and 7 days
            // days is a good interval               
            case(strtotime('-1 week', $dateto) < $datefrom):
                $day_difference = 1;
                while (strtotime('-'.$day_difference.' day', $dateto) >= $datefrom)
                {
                    $day_difference++;
                }
               
                $datediff = $day_difference;
                $res = ($datediff==1) ? 'yesterday' : $datediff.' days ago';
                break;
            // If difference is between 1 week and 30 days
            // weeks is a good interval           
            case(strtotime('-1 month', $dateto) < $datefrom):
                $week_difference = 1;
                while (strtotime('-'.$week_difference.' week', $dateto) >= $datefrom)
                {
                    $week_difference++;
                }
               
                $datediff = $week_difference;
                $res = ($datediff==1) ? 'last week' : $datediff.' weeks ago';
                break;           
            // If difference is between 30 days and 365 days
            // months is a good interval, again, the same thing
            // applies, if the 29th February happens to exist
            // between your 2 dates, the function will return
            // the 'incorrect' value for a day
            case(strtotime('-1 year', $dateto) < $datefrom):
                $months_difference = 1;
                while (strtotime('-'.$months_difference.' month', $dateto) >= $datefrom)
                {
                    $months_difference++;
                }
               
                $datediff = $months_difference;
                $res = ($datediff==1) ? $datediff.' month ago' : $datediff.' months ago';

                break;
            // If difference is greater than or equal to 365
            // days, return year. This will be incorrect if
            // for example, you call the function on the 28th April
            // 2008 passing in 29th April 2007. It will return
            // 1 year ago when in actual fact (yawn!) not quite
            // a year has gone by
            case(strtotime('-1 year', $dateto) >= $datefrom):
                $year_difference = 1;
                while (strtotime('-'.$year_difference.' year', $dateto) >= $datefrom)
                {
                    $year_difference++;
                }
               
                $datediff = $year_difference;
                $res = ($datediff==1) ? $datediff.' year ago' : $datediff.' years ago';
                break;
               
        }
        return $res;
    }

    
    function getMonth($monthnum, $startyear) 
{
	$month = $monthnum % 12;
	$year = '';
	if ($month === 6) 
	{
		$year = '<br/>' . (floor($monthnum / 12) + $startyear);
	}
	return $month . $year;
}
    
    
?>
