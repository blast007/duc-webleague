<?php
	if (!isset($account_needs_to_be_converted) || !($account_needs_to_be_converted))
	{
		require_once '../CMS/navi.inc';
		
		if ((isset($_SESSION['user_logged_in'])) &&	($_SESSION['user_logged_in']))
		{
			echo 'already logged in';
			die();
		}
	}
	
	$db_imported = new db_import;
	$account_old_website = htmlent($db_imported->old_website());
	
	if (!(isset($_POST['local_login_wanted']) && $_POST['local_login_wanted']))
	{
		echo '<form action="' . baseaddress() . 'Login/'. '" method="post">' . "\n";
		echo '<p class="first_p">' . "\n";
		if ($site->force_external_login_when_trying_local_login())
		{
			$site->write_self_closing_tag('input type="submit" name="local_login_wanted" value="Upgrade my old account and connect it to global one"');
		} else
		{
			$site->write_self_closing_tag('input type="submit" name="local_login_wanted" value="Local login"');
		}
		echo '</p>' . "\n";
		echo '</form>' . "\n";
	}
	if (isset($_POST['local_login_wanted']) && $_POST['local_login_wanted'])
	{
		echo '<div class="static_page_box">' . "\n";
		
		echo '<p class="first_p">';
		if ($site->convert_users_to_external_login())
		{
			require_once '../CMS/login_module_list.php';
			if (isset($module['bzbb']) && ($module['bzbb']))
			{
				echo '<strong><span class="unread_messages">Before you continue make absolutely sure your account here and the my.bzflag.org/bb/ (forum) account have exactly the same username or you will give someone else access to your account and that access can never be revoked.</span></strong></p>';
			}
		}
		
		echo '<p>Enter login data from <strong>' . $account_old_website . '</strong> here!</p>';
		echo "\n";
		require_once 'form.inc';
		echo '<p>Note: Only global login has the ability to allow more than standard permissions at the moment.</p>' . "\n";
	}
?>