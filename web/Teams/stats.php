<?php
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	$path = (pathinfo(realpath('./')));
	$name = $path['basename'];
	
	$display_page_title = $name;
	require_once (dirname(dirname(__FILE__)) . '/CMS/index.inc');
	require realpath('../CMS/navi.inc');
	
	$site = new siteinfo();
	
	   
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
	
	
	$connection = $site->connect_to_db();
	$months = 12;
	$topteams = 8;
	if (isset($_GET['months']) && intval($_GET['months']) > 0 )
	{
		$months = intval($_GET['months']);
	}
	
	if (isset($_GET['teams']) && intval($_GET['teams']) > 0 )
	{
		$topteams = intval($_GET['teams']);
	}

	echo '<h1 class="teams">Teams activity</h1>';
	
	echo '<div class="main-box">';
		$query_overall = 'SELECT MONTH(timestamp) as month, YEAR(timestamp) as year,  count(id) as match_number'
		. ' FROM matches WHERE PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"),DATE_FORMAT(timestamp,"%Y%m")) < ' . $months
		. ' GROUP BY month, year ORDER BY year asc, month asc';
		
		
		
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
		$chart_values = array('');
		$team_names = array('Overall');
		$startyear = 2000;
		
		$current_month = (12 * (date("Y")-$startyear)) + date("m"); 	
		$first_month = $current_month - $months;
		
		while ($row = mysql_fetch_array($result))
		{
			$match_stats[$i]['period'] = $row['month'] . '/' . $row['year'];
			$match_stats[$i]['match_number'] = $row['match_number'];
			$match_stats[$i]['month_number'] = (12 * ($row['year']-$startyear)) + $row['month']; 			
			if ($i == 0 && $match_stats[$i]['month_number'] > ($first_month)) 
			{
				for ($j=1; $j < ($match_stats[$i]['month_number'] - ($first_month)); $j++)
				{
					$chart_periods .= "'" . getMonth($first_month + $j, $startyear) . "',";
				}
		 	
			}
			
			if ($i != 0)
			{
				//if some team miss few months of activity
				if ($match_stats[$i]['month_number'] > ($match_stats[$i-1]['month_number'] + 1) )
				{
					for ($j=1; $j < ($match_stats[$i]['month_number'] - ($match_stats[$i-1]['month_number'])); $j++)
					{
						$chart_periods .= ",'" . getMonth($match_stats[$i-1]['month_number'] + $j, $startyear) . "'";
						$chart_values[0] .= ",0";
					}
				} 								
				$chart_periods.= ',';
				$chart_values[0] .= ',';
			}
			$chart_periods .= "'" . $row['month'] . (($row['month'] === '6')? '<br/>' . $row['year']:'')  . "'";
			$chart_values[0] .=  $match_stats[$i]['match_number'] ;
			
			$i++;
		}		
		echo '<div id="chart-container-1"></div>' . "\n";
		
		$query_topteams = 'SELECT t.id, t.name, count(m.id) as match_number '
		. ' FROM teams t LEFT JOIN matches m ON (m.team1_teamid = t.id OR m.team2_teamid = t.id) '
		. ' WHERE PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"),DATE_FORMAT(timestamp,"%Y%m")) < ' . $months
		. ' GROUP BY t.id, t.name HAVING match_number > 0 ORDER BY match_number DESC limit ' . $topteams;
		
		if (!($result = @$site->execute_query('matches', $query_topteams, $connection)))
		{
			$site->dieAndEndPage('Could not find matches played.');
		}
		$team_no = 0;
		while ($row = mysql_fetch_array($result))
		{
			$team_no++;
			$chart_values[$team_no] = '';
			$team_id = $row['id'];
			$team_names[$team_no] = $row['name']; 
			$match_stats = array();
			$row_no = 0;
			
			$query_teamstats = 'SELECT MONTH(timestamp) as month, YEAR(timestamp) as year,  count(id) as match_number'
		. ' FROM matches WHERE PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"),DATE_FORMAT(timestamp,"%Y%m")) < ' . $months
		. ' AND (team1_teamid = ' . $team_id . ' OR team2_teamid = ' . $team_id . ')'
		. ' GROUP BY month, year ORDER BY year asc, month asc';
			
			if (!($result_team = @$site->execute_query('matches', $query_teamstats, $connection)))
			{
				$site->dieAndEndPage('Could not find matches played for team ' . $row['name'] . '.');
			}
						
			while ($row_team = mysql_fetch_array($result_team))
			{
				$match_stats[$i]['match_number'] = $row_team['match_number'];
				//count first month with results;
				$match_stats[$i]['month_number'] = (12 * ($row_team['year']-$startyear)) + $row_team['month']; 			
				if ($row_no == 0) 
				{
					//fill with 0s on start, if neccesary
					if ($match_stats[$i]['month_number'] > ($first_month) )
					{
						for ($j=1; $j < ($match_stats[$i]['month_number'] - ($first_month)); $j++)
						{
							$chart_values[$team_no] .= "0,";
							$row_no++;
						}
					} 				
				}
				else
				{
					//if some team miss few months of activity
					if ($match_stats[$i]['month_number'] > ($match_stats[$i-1]['month_number'] + 1) )
					{
						for ($j=1; $j < ($match_stats[$i]['month_number'] - ($match_stats[$i-1]['month_number'])); $j++)
						{
								$chart_values[$team_no] .= ",0";
								$row_no++;
						}
					} 								
					$chart_values[$team_no] .= ',';
				}
				$chart_values[$team_no] .=  $match_stats[$i]['match_number'] ;
				$row_no++;
				$i++;
			}	
			//fill end with 0 for team 
			while (	$row_no < $months)
			{
				$chart_values[$team_no] .= ',0';
				$row_no++;
			}
			
		}
		
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
				enabled: true
			},
			tooltip: {
		         shared: true,
		         crosshairs: true
		      },
			plotOptions: {
			
			},
		    series: [
		 		<?php 
		 			for ($i=0; $i <= $team_no; $i++)
		 			{
		 				echo "{ name: '" . addslashes(htmlent_decode($team_names[$i])) . "', data: [$chart_values[$i]] }\n";
		 				if ($i < $team_no) echo ',';
		 			}
		 		
		 		
		 		?>    
		 		]
	      });
	   });	
	</script>
	

</div>
</body>
</html>
