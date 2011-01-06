<?php
	require_once realpath('../CMS/siteinfo.php');
	echo '<form action="' . baseaddress() . 'Login/'. '" method="post">' . "\n";
	echo '<div class="p">Name:</div>' . "\n";
	echo '<p class="first_p">' . "\n";
	$site->write_self_closing_tag('input type="text" class="small_input_field" name="loginname" value="" maxlength="300"');
	echo '</p>' . "\n";
	
	echo '<div class="p">Password:</div>' . "\n";
	echo '<p class="first_p">' . "\n";
	$site->write_self_closing_tag('input type="password" name="pw" value="" maxlength="300"');
	echo '</p>' . "\n";
	
	echo '<div>' . "\n";
	$site->write_self_closing_tag('input type="hidden" name="module" value="local" maxlength="300"');
	echo '</div>' . "\n";
	
	echo '<div>' . "\n";
	if ($site->force_external_login_when_trying_local_login())
	{
		$site->write_self_closing_tag('input type="submit" value="Update"');
	} else
	{
		$site->write_self_closing_tag('input type="submit" value="Login"');
	}
	echo '</div>' . "\n";
?>
</form>
