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
	
	$allow_manage_polls = false;
	if (isset($_SESSION['allow_manage_polls']))
	{
		if (($_SESSION['allow_manage_polls']) === true)
		{
			$allow_manage_polls = true;
		}
	}
	
	echo '<h1 class="polls">Polls management</h1>';
	echo '<div class="main-box">' . "\n";
	
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
	
	//New poll
	if (isset($_POST['create']) && $allow_manage_polls)
	{
		createPoll();	
	}	
	
	//Delete poll
	if (isset($_GET['delete']) && $allow_manage_polls)
	{
		$id =  intval($_GET['delete']);
		deletePoll($id);	
	}
	
	//Publish poll
	if (isset($_GET['publish']) && $allow_manage_polls)
	{
		$id =  intval($_GET['publish']);
		publishPoll($id, 'yes');
	}
	
	//Block poll
	if (isset($_GET['block']) && $allow_manage_polls)
	{
		$id =  intval($_GET['block']);
		publishPoll($id, 'no');	
	}
	
	//Change poll
	if (isset($_POST['change']) && $allow_manage_polls)
	{
		$id =  intval($_POST['change']);
		$view_results = 0;
		if (isset($_POST['view_results']) && $_POST['view_results'] > 0 ) $view_results = 1;
		updatePollPresentation($id, $view_results);	
	}
	$editedid = null;
	//edit poll
	if (isset($_GET['edit']) && $allow_manage_polls)
	{
		$editedid =  intval($_GET['edit']);
	}
	
	if (isset($_POST['update']) && isset($_POST['pollid'])  && isset($_POST['poll_question']) && $allow_manage_polls)
	{
		$id =  intval($_POST['pollid']);
		$question = $_POST['poll_question'];
		updatePollQuestion($id, $question);	
	}
	
	
	
	
	showPollsList($allow_manage_polls, $editedid);
	
?>
	</div>
</div>
</body>
</html>