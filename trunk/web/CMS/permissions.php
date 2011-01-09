<?php
	function no_permissions()
	{
		// delete bzid
		unset($_SESSION['bzid']);
		
		// no external id by default
		$_SESSION['external_id'] = 0;
		
		// assume local login by default
		$_SESSION['external_login'] = false;
		
		// can change debug sql setting
		$_SESSION['allow_change_debug_sql'] = false;
		
		// set all permission to false by default
		// permissions for news page
		$_SESSION['allow_set_different_news_author'] = false;
		$_SESSION['allow_add_news'] = false;
		$_SESSION['allow_edit_news'] = false;
		$_SESSION['allow_delete_news'] = false;
		
		
		// set all permission to false by default
		// permissions for spawnlist page
		$_SESSION['allow_set_different_spawnlist_author'] = false;
		$_SESSION['allow_add_spawnlist'] = false;
		$_SESSION['allow_edit_spawnlist'] = false;
		$_SESSION['allow_delete_spawnlist'] = false;
		
		
		// permissions for all static pages
		$_SESSION['allow_edit_static_pages'] = false;
		
		// permissions for bans page
		$_SESSION['allow_set_different_bans_author'] = false;
		$_SESSION['allow_add_bans'] = false;
		$_SESSION['allow_edit_bans'] = false;
		$_SESSION['allow_delete_bans'] = false;
		
		// permissions for private messages
		$_SESSION['allow_add_messages'] = false;
		// private messages are never supposed to be edited at all by a 3rd person
		$_SESSION['allow_edit_messages'] = true;
		$_SESSION['allow_delete_messages'] = true;
		
		// team permissions
		$_SESSION['allow_kick_any_team_members'] = false;
		$_SESSION['allow_edit_any_team_profile'] = false;
		$_SESSION['allow_delete_any_team'] = false;
		$_SESSION['allow_invite_in_any_team'] = false;
		$_SESSION['allow_reactivate_teams'] = false;
		
		
		// user permissions
		$_SESSION['allow_edit_any_user_profile'] = false;
		$_SESSION['allow_add_admin_comments_to_user_profile'] = false;
		$_SESSION['allow_ban_any_user'] = false;
		$_SESSION['allow_assign_user_bbid'] = false;
		
		// visits log permissions
		$_SESSION['allow_view_user_visits'] = false;
		
		// match permissions
		$_SESSION['allow_add_match'] = false;
		$_SESSION['allow_edit_match'] = false;
		$_SESSION['allow_delete_match'] = false;
		
		// seasons permissions
		$_SESSION['allow_add_season'] = false;
		$_SESSION['allow_edit_season'] = false;
		$_SESSION['allow_delete_season'] = false;
		
		// server tracker permissions
		$_SESSION['allow_watch_servertracker'] = false;
		
		// TODO permissions
		$_SESSION['allow_view_todo'] = false;
		$_SESSION['allow_edit_todo'] = false;
		
		// aux permissions
		$_SESSION['IsAdmin'] = false;
		
		// shoutbox permissions
		$_SESSION['allow_moderate_shoutbox'] = false;
		
	}
	
	function allow_change_debug_sql()
	{
		if (!($_SESSION['allow_change_debug_sql']))
		{
			$_SESSION['allow_change_debug_sql'] = true;
		}
	}
	
	function allow_set_different_news_author()
	{
		if (!($_SESSION['allow_set_different_news_author']))
		{
			$_SESSION['allow_set_different_news_author'] = true;
		}
	}
	
	function allow_add_news()
	{
		if (!($_SESSION['allow_add_news']))
		{
			$_SESSION['allow_add_news'] = true;
		}
	}
	
	function allow_edit_news()
	{
		if (!($_SESSION['allow_edit_news']))
		{
			$_SESSION['allow_edit_news'] = true;
		}
	}
	
	function allow_delete_news()
	{
		if (!($_SESSION['allow_delete_news']))
		{
			$_SESSION['allow_delete_news'] = true;
		}
	}
	
	function allow_set_different_spawnlist_author()
	{
		if (!($_SESSION['allow_set_different_spawnlist_author']))
		{
			$_SESSION['allow_set_different_spawnlist_author'] = true;
		}
	}
	
	function allow_add_spawnlist()
	{
		if (!($_SESSION['allow_add_spawnlist']))
		{
			$_SESSION['allow_add_spawnlist'] = true;
		}
	}
	
	function allow_edit_spawnlist()
	{
		if (!($_SESSION['allow_edit_spawnlist']))
		{
			$_SESSION['allow_edit_spawnlist'] = true;
		}
	}
	
	function allow_delete_spawnlist()
	{
		if (!($_SESSION['allow_delete_spawnlist']))
		{
			$_SESSION['allow_delete_spawnlist'] = true;
		}
	}
	
	
	
	function allow_edit_static_pages()
	{
		if (!($_SESSION['allow_edit_static_pages']))
		{
			$_SESSION['allow_edit_static_pages'] = true;
		}
	}
	
	function allow_set_different_bans_author()
	{
		if (!($_SESSION['allow_set_different_bans_author']))
		{
			$_SESSION['allow_set_different_bans_author'] = true;
		}
	}
	
	function allow_add_bans()
	{
		if (!($_SESSION['allow_add_bans']))
		{
			$_SESSION['allow_add_bans'] = true;
		}
	}
	
	function allow_edit_bans()
	{
		if (!($_SESSION['allow_edit_bans']))
		{
			$_SESSION['allow_edit_bans'] = true;
		}
	}
	
	function allow_delete_bans()
	{
		if (!($_SESSION['allow_delete_bans']))
		{
			$_SESSION['allow_delete_bans'] = true;
		}
	}
	
	function allow_add_messages()
	{
		if (!($_SESSION['allow_add_messages']))
		{
			$_SESSION['allow_add_messages'] = true;
		}
	}
	
	function allow_delete_messages()
	{
		if (!($_SESSION['allow_delete_messages']))
		{
			$_SESSION['allow_delete_messages'] = true;
		}
	}
	
	function allow_kick_any_team_members()
	{
		if (!($_SESSION['allow_kick_any_team_members']))
		{
			$_SESSION['allow_kick_any_team_members'] = true;
		}
	}
	
	function get_allow_kick_any_team_members()
	{
		$reply = false;
		if (isset($_SESSION['allow_kick_any_team_members']))
		{
			if ($_SESSION['allow_kick_any_team_members'] === true)
			{
				$reply = true;
			}
		}
		return $reply;
	}
	
	
	function allow_edit_any_team_profile()
	{
		if (!($_SESSION['allow_edit_any_team_profile']))
		{
			$_SESSION['allow_edit_any_team_profile'] = true;
		}
	}
	
	function allow_delete_any_team()
	{
		if (!($_SESSION['allow_delete_any_team']))
		{
			$_SESSION['allow_delete_any_team'] = true;
		}
	}
	
	function allow_invite_in_any_team()
	{
		if (!($_SESSION['allow_invite_in_any_team']))
		{
			$_SESSION['allow_invite_in_any_team'] = true;
		}
	}
	
	function allow_reactivate_teams()
	{
		if (!($_SESSION['allow_reactivate_teams']))
		{
			$_SESSION['allow_reactivate_teams'] = true;
		}
	}
	
	function allow_edit_any_user_profile()
	{
		if (!($_SESSION['allow_edit_any_user_profile']))
		{
			$_SESSION['allow_edit_any_user_profile'] = true;
		}
	}
	
	function allow_add_admin_comments_to_user_profile()
	{
		if (!($_SESSION['allow_add_admin_comments_to_user_profile']))
		{
			$_SESSION['allow_add_admin_comments_to_user_profile'] = true;
		}
	}
	
	function allow_assign_user_bbid()
	{
		if (!($_SESSION['allow_assign_user_bbid']))
		{
			$_SESSION['allow_assign_user_bbid'] = true;
		}
	}
	
	function allow_ban_any_user()
	{
		if (!($_SESSION['allow_ban_any_user']))
		{
			$_SESSION['allow_ban_any_user'] = true;
		}
	}
	
	function allow_view_user_visits()
	{
		if (!($_SESSION['allow_view_user_visits']))
		{
			$_SESSION['allow_view_user_visits'] = true;
		}
	}
		
	function allow_add_match()
	{
		if (!($_SESSION['allow_add_match']))
		{
			$_SESSION['allow_add_match'] = true;
		}
	}
	
	function allow_edit_match()
	{
		if (!($_SESSION['allow_edit_match']))
		{
			$_SESSION['allow_edit_match'] = true;
		}
	}
	
	function allow_delete_match()
	{
		if (!($_SESSION['allow_delete_match']))
		{
			$_SESSION['allow_delete_match'] = true;
		}
	}
	
	function allow_add_season()
	{
		if (!($_SESSION['allow_add_season']))
		{
			$_SESSION['allow_add_season'] = true;
		}
	}
	
	function allow_edit_season()
	{
		if (!($_SESSION['allow_edit_season']))
		{
			$_SESSION['allow_edit_season'] = true;
		}
	}
	
	function allow_delete_season()
	{
		if (!($_SESSION['allow_delete_season']))
		{
			$_SESSION['allow_delete_season'] = true;
		}
	}
	
	function allow_watch_servertracker()
	{
		if (!($_SESSION['allow_watch_servertracker']))
		{
			$_SESSION['allow_watch_servertracker'] = true;
		}
	}
	
	function allow_view_todo()
	{
		if (!($_SESSION['allow_view_todo']))
		{
			$_SESSION['allow_view_todo'] = true;
		}
	}
	
	function allow_edit_todo()
	{
		if (!($_SESSION['allow_edit_todo']))
		{
			$_SESSION['allow_edit_todo'] = true;
		}
	}
	
	function allow_moderate_shoutbox()
	{
		if (!($_SESSION['allow_moderate_shoutbox']))
		{
			$_SESSION['allow_moderate_shoutbox'] = true;
		}
	}

	
	function is_admin()
	{
		if (!($_SESSION['IsAdmin']))
		{
			$_SESSION['IsAdmin'] = true;
		}
	}
?>