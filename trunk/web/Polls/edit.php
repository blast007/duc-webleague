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
		
	
	
	
	if (isset($_POST['create']) && $allow_manage_polls)
	{
		createAnswer($pollid);	
	}	
	
	if (isset($_GET['delete']) && $allow_manage_polls && isset($_GET['pos']) && $pollid >0)
	{
		$id =  intval($_GET['delete']);
		$pos = intval($_GET['pos']);
		deleteAnswer($id,$pos);	
	}
	
	if (isset($_GET['up']) && $allow_manage_polls && isset($_GET['answer']) && $pollid >0)
	{
		$pos =  intval($_GET['up']);
		$id = intval($_GET['answer']);
		moveUpAnswer($id, $pos);	
	}
	
	if (isset($_GET['down']) && $allow_manage_polls && isset($_GET['answer']) && $pollid >0)
	{
		$pos =  intval($_GET['down']);
		$id = intval($_GET['answer']);
		moveDownAnswer($id, $pos);	
	}
	
	if (isset($_POST['edit']) && $allow_manage_polls && isset($_POST['answer']) && $pollid >0)
	{
		$id = intval($_POST['answer']);
		updateAnswer($id);	
	}
	
	if (isset($_GET['reset']) && $allow_manage_polls && $pollid >0)
	{
		$id = intval($_GET['reset']);
		resetVotes($id);	
	}
	
	echo '<h2>' . $poll_question . '</h2>';
	
	
	showAnswersList($allow_manage_polls, $pollid);
	
	echo '</div>';
?>
	</div>
</div>
</body>
</html>