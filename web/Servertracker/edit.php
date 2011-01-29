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
	require ('servers.inc');
	
	$connection = $site->connect_to_db();
	$viewerid = (int) getUserID();
	$pollid = 0;
	
	echo '<h1 class="tools">Servers management</h1>';
	
	echo '<div class="simple-paging">';
	echo '<a class="button" href="/Servertracker/">Overview</a>' . "\n";
	echo '</div>' . "\n";
	
	echo '<div class="main-box">' . "\n";
	
	$allow_manage_servers = false;
	if (isset($_SESSION['allow_manage_servers']))
	{
		if (($_SESSION['allow_manage_servers']) === true)
		{
			$allow_manage_servers = true;
		}
	}
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p class="first_p">You need to login in order to manage servers!</p>';
		$site->dieAndEndPageNoBox();
	}
	
	// only allow looking when having the permission
	if ($allow_manage_servers === false)
	{
		$site->dieAndEndPageNoBox('You (id=' . sqlSafeString($viewerid) . ') have no permissions to manage servers!');
	}
	
	
	// in any case you need to be logged in to view the visits log
	if ($viewerid === 0)
	{
		echo '<p class="first_p">You need to login in order to manage servers!</p>';
		$site->dieAndEndPageNoBox();
	}

		
	if (isset($_POST['create']) && $allow_manage_servers)
	{
		createServer($pollid);	
	}	
	
	if (isset($_GET['delete']) && $allow_manage_servers)
	{
		$id =  intval($_GET['delete']);
		deleteServer($id);	
	}


	
	showServersList($allow_manage_servers);
	
	echo '</div>';
?>
	</div>
</div>
</body>
</html>