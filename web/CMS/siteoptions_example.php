<?php
	function domain()
	{
		// return 'my.bzflag.org';
		return '192.168.1.10';
	}
	
	function basepath()
	{
		return '/~spiele/league_svn/ts/';
	}
	
	function database_to_be_imported()
	{
		return 'bzleague_guleague';
	}
	function old_website_name()
	{
		return 'league.bzflag.net';
	}
	
	function convert_users_to_external_login_if_no_external_login_id_set()
	{
		return true;
	}
	
	function force_external_login_only()
	{
		return true;
	}
	
	// make posts anonymous
	function force_username(&$section)
	{
		if (strcmp($section, 'bans') === 0)
		{
			return 'DucCouncil';
		} else
		{
			return '';
		}
	}
	
	// the name displayed in mails sent by the system
	function system_username()
	{
		return 'CTF League System';
	}
	
	function timezone()
	{
		// set the timezone used
		// values like Europe/Berlin or UTC are accepted
		// look at http://www.php.net/manual/en/timezones.php
		// for a complete list of supported timezones
		return 'Europe/Berlin';
	}
	
	function xhtml_on()
	{
		// nl2br needs php newer or equal to 4.0.5 to support xhtml
		// if php version is higher or equal to 4.0.5 but lower than 5.3
		// then xhtml will be always on
		// if php version is lower than 4.0.5 then xhtml will be always off
		// see http://www.php.net/manual/en/function.nl2br.php
		return true;
	}
	
	function www_required()
	{
		return false;
	}
	
	function db_used_custom_name()
	{
		// this is the database name this league website will use
		return 'ts-CMS';
	}
	
	function debug_sql_custom()
	{
		// show sql queries by default? use true or false
		return true;
	}
	
	function bbcode_lib_path()
	{
		return ((dirname(__FILE__)) . '/nbbc-wrapper.php');
	}
	
	function bbcode_class()
	{
		return 'wrapper';
	}
	
	function bbcode_sets_linebreaks()
	{
		return true;
	}
	
	function bbcode_command()
	{
		return 'Parse';
	}
	
	function favicon()
	{
		// either return an empty string or path to favicon
		return '';
	}
	
	class pw_secret
	{
		function mysqlpw_secret()
		{
			return 'insert mysql user password here';
		}
		
		function mysqluser_secret()
		{
			return 'insert mysql user name here';
		}
	}
	
	function maintain_inactive_teams()
	{
		// remove or deactivate teams that do not match anymore?
		return true;
	}
	
	function maintain_inactive_teams_with_active_players()
	{
		// players from inactive team do login but they do not match
		// remove or deactive the teams with active players?
		return false;
	}
?>