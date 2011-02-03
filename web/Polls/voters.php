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
	require ('polls.inc');
	
	$connection = $site->connect_to_db();
	$viewerid = (int) getUserID();
	$pollid = 0;
	$poll_question= "";
	
	echo '<h1 class="polls">Polls management</h1>';
	
	echo '<div class="simple-paging">';
	echo '<a class="button" href="/Polls/">Overview</a>' . "\n";
	echo '</div>' . "\n";
	
	echo '<div class="main-box">' . "\n";
	
	$allow_manage_polls = false;
	if (isset($_SESSION['allow_manage_polls']))
	{
		if (($_SESSION['allow_manage_polls']) === true)
		{
			$allow_manage_polls = true;
		}
	}
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p class="first_p">You need to login in order to manage polls!</p>';
		$site->dieAndEndPageNoBox();
	}
	
	// only allow looking when having the permission
	if ($allow_manage_polls === false)
	{
		$site->dieAndEndPageNoBox('You (id=' . sqlSafeString($viewerid) . ') have no permissions to manage polls!');
	}
	
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p class="first_p">You need to login in order to manage polls!</p>';
		$site->dieAndEndPageNoBox();
	}

	if (isset($_REQUEST['poll']))
	{
		$pollid = intval($_REQUEST['poll']);		
	}
	
	$query = ("SELECT * FROM polls_questions WHERE id = $pollid");
	if (!($result = @$site->execute_query('polls_questions', $query, $connection)) || mysql_num_rows($result) == 0)
	{
		$site->dieAndEndPageNoBox('Wrong poll id.');
	}	else
	{
		$row = mysql_fetch_array($result);
		$poll_question = $row['question'];
	}  
	
	
	
	echo '<h2>Voted for ' . $poll_question . '</h2>';
	
	
	showVotersList($allow_manage_polls, $pollid);
	
	echo '</div>';
?>
	</div>
</div>
</body>
</html>