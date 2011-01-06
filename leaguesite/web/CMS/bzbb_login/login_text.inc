<?php
	require_once '../CMS/navi.inc';
	echo '<div class="static_page_box">' . "\n";
	echo '<p class="first_p">Please log in using your account at <a href=';
	require_once '../CMS/siteinfo.php';
	//$url = urlencode(baseaddress() . 'CMS/' . '?bzbbauth=%25TOKEN%25,%25USERNAME%25');
	//$url = urlencode(baseaddress() . 'CMS/' . '?bzbbauth=%TOKEN%&username=%USERNAME%');
	//$url = (baseaddress() . 'CMS/' . '?bzbbauth=%TOKEN%&username=%25USERNAME%25');
	//$url = (baseaddress() . 'CMS/' . '?bzbbauth=%25TOKEN%25,%25USERNAME%25');
	$url = urlencode(baseaddress() . 'Login/' . '?bzbbauth=%TOKEN%,%USERNAME%');
	require_once 'index.php';
	echo '"' . htmlspecialchars('http://my.bzflag.org/weblogin.php?action=weblogin&url=') . $url;
	echo '" class="button loginbutton">my.bzflag.org (BZBB)</a>.</p>' . "\n";
?>
