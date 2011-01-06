<?php
	// do not attach SID to URL as this can cause security problems
	// especially when a user shares an URL that includes a SID
	// use cookies as workaround
	ini_set ('session.use_trans_sid', 0);
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	@session_start();
	
	require_once 'login_module_list.php';
	$module = active_login_modules();
	$output = '';
	
	// load description presented to user
	$page_title = 'ts-CMS';
	require_once 'index.inc';
	
	$auth_performed = false;
	
	ob_start();

	if (!(isset($_SESSION['user_logged_in'])) || !($_SESSION['user_logged_in']))
	{
		// load modules to check input and buffer output
		// the buffer is neccessary because the modules might need to set cookies for instance
		if (isset($module['bzbb']) && ($module['bzbb']))
		{
			include_once 'bzbb_login/index.php';
		}
		
		if (isset($module['local']) && ($module['local']))
		{
			ob_start();
			include_once 'local_login/index.php';
		}
		
		if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
		{
			$auth_performed = true;
		}
	}
	
	if (!$auth_performed && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'])
	{
		require_once '../CMS/navi.inc';
		echo '<div class="static_page_box">' . "\n";
		$output_buffer .= ob_get_contents();
		ob_end_clean();
		// write output buffer
		echo $output_buffer;
		echo '<p class="first_p">Login was already successful.</p>' . "\n";
		$site->dieAndEndPage();
	}
	
	if (!(isset($_SESSION['user_logged_in'])) || !($_SESSION['user_logged_in']))
	{
		// user explicitly does not want an external login and confirmed it already
		if (!(isset($_POST['local_login_wanted']) && $_POST['local_login_wanted']))
		{
			if (isset($module['bzbb']) && ($module['bzbb']))
			{
				include_once 'bzbb_login/login_text.inc';
			}
		}
		
		if (!( (isset($_GET['bzbbauth'])) && ($_GET['bzbbauth']) ))
		{
			if (!(isset($_POST['local_login_wanted']) && $_POST['local_login_wanted']) && isset($module['local']) && ($module['local']))
			{
				echo '<strong>or</strong>';
				$site->write_self_closing_tag('br');
				$site->write_self_closing_tag('br');
			}
		}
		
		if (isset($module['local']) && ($module['local']))
		{
			include_once 'local_login/login_text.inc';
		}
	}
?>