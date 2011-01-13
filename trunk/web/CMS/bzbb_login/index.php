<?php
if ( (isset($_GET['bzbbauth'])) && ($_GET['bzbbauth']) )
{
	require '../CMS/bzbb_login/checkToken.php';
	require_once '../CMS/permissions.php';
	
	if ($banned_user)
	{
		require_once '../CMS/navi.inc';
		echo '<div class="static_page_box">' . "\n";
		$error_msg = '<p class="first_p">Login failed: The returned values could not be validated!</p>' . "\n";
		$site->dieAndEndPage($error_msg);
	}
	
	// initialise permissions
	no_permissions();
	
	// groups used for permissions
	// each group can use the fine grained permission system
	$groups = Array ('VERIFIED','DUCATI.REFEREE','DUCATI.COUNCIL');
	$args = explode (',', urldecode($_GET['bzbbauth']));
	// $args[0] is token, $args[1] is callsign
	if (!$info = validate_token ($args[0], $args[1], $groups, false))
	{
		// login did not work, removing permissions not necessary as additional permissions where never granted
		// after permissions were removed at the beginning of the file
		require_once '../CMS/navi.inc';
		echo '<div class="static_page_box">' . "\n";
		$error_msg = '<p class="first_p">Login failed: The returned values could not be validated! You may check your username and password.</p>' . "\n";
		$error_msg .= '<p>Please <a href="./">try again</a>.</p>' . "\n";
		if (isset($site))
		{
			$site->dieAndEndPage($error_msg);
		} else
		{
			die($error_msg);
		}
	}
	
	// NOTE: invalid bzid should be set to -1
	
	// assume user is in the
	// VERIFIED group
	// because login worked
		
	// since we use a global login for auth any user should be in that group
	$_SESSION['username'] = $info['username'];
	$external_login_id = $info['bzid'];
	$_SESSION['bzid'] = $external_login_id;
	$_SESSION['user_logged_in'] = true;
	
	// permissions for private messages
	allow_add_messages();
	allow_delete_messages();
	
	// permissions for spawnlist page
	allow_add_spawnlist();
		
	// server tracker permissions
	allow_watch_servertracker();
	
	
	
	// test only for DUCATI.ADMINS group
	$group_test = array_slice($groups, 1, 1);
	$in_group = false;
	foreach ($info['groups'] as $one_group)
	{
		// case insensitive comparison
		if (strcasecmp($one_group, $group_test[0]) === 0)
		{
			$in_group = true;
			break;
		}
	}
	unset($one_group);
	
	if ($in_group === true)
	{
		if ($site->debug_sql())
		{
			echo '<p>DUC league referee detected</p>';
		}
		// DUCATI.REFEREE group
		
		// match permissions
		allow_add_match();
		allow_edit_match();
		
		// permissions for news page
		allow_add_news();
		allow_edit_news();
		allow_delete_news();
	}
	
	// test only for DUCATI.ADMINS group
	$in_group = false;
	$group_test = array_slice($groups, -1, 1);
	foreach ($info['groups'] as $one_group)
	{
		// case insensitive comparison
		if (strcasecmp($one_group, $group_test[0]) === 0)
		{
			$in_group = true;
			break;
		}
	}
	if ($in_group === true)
	{
		if ($site->debug_sql())
		{
			echo '<p>DUC league council detected</p>';
		}
		// DUCATI.COUNCIL group
		
		// can change debug sql setting
		allow_change_debug_sql();
		
		// permissions for news page
		allow_add_news();
		allow_edit_news();
		allow_delete_news();
		
		
		// permissions for spawnlist page
		allow_add_spawnlist();
		allow_edit_spawnlist();
		allow_delete_spawnlist();
		
		// permissions for all static pages
		allow_edit_static_pages();
		
		// permissions for bans page
		allow_add_bans();
		allow_edit_bans();
		allow_delete_bans();
		
		// sitebans
		allow_manage_sitebans();
		
		// permissions for team page
		allow_kick_any_team_members();
		allow_edit_any_team_profile();
		allow_invite_in_any_team();
		allow_delete_any_team();
		allow_reactivate_teams();
		
		// user permissions
		allow_edit_any_user_profile();
		allow_add_admin_comments_to_user_profile();
		allow_ban_any_user();
		allow_assign_user_bbid();
		
		// visits log permissions
		allow_view_user_visits();
		
		// match permissions
		allow_add_match();
		allow_edit_match();
		allow_delete_match();
		
		//seasons permissions
		allow_add_season();
		allow_edit_season();
		allow_delete_season();
		
		// server tracker permissions
		allow_watch_servertracker();
		
		// TODO permissions
		allow_view_todo();
		allow_edit_todo();
		
		// aux permissions
		is_admin();
		allow_moderate_shoutbox();
	}
	
	if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		if (isset($_SESSION['bzid']) && (!(strcmp($_SESSION['bzid'], '-1') == 0) || !(strcmp($_SESSION['bzid'], '0') == 0)))
		{
			$_SESSION['external_id'] = $_SESSION['bzid'];
		}
		
		$_SESSION['external_login'] = true;
		$external_login_id = $_SESSION['external_id'];
//		echo '<div class="static_page_box">' . "\n";
//		echo '<p class="first_p">Login information validated!</p>' . "\n";
	}
}
?>