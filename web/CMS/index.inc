<?php
	require_once 'siteinfo.php';
	$site = new siteinfo();
	
	require_once (dirname(dirname(__FILE__)) . '/Bans/sitebans.inc');
	
	if ($site->use_xtml())
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\n";
		echo '     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	} else
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"' . "\n";
		echo '        "http://www.w3.org/TR/html4/strict.dtd">';
	}
	echo "\n" . '<html';
	if ($site->use_xtml())
	{
		echo ' xmlns="http://www.w3.org/1999/xhtml"';
	}
	echo '>' . "\n";
	echo '<head>' . "\n";
	$site->write_self_closing_tag('meta content="text/html; charset=utf-8" http-equiv="content-type"');

	require (dirname(dirname(__FILE__)) . '/stylesheet.inc');
	
	// display favicon, if specified
	if (!(strcmp($site->favicon_path(), '') === 0))
	{
		echo '	';
		echo $site->write_self_closing_tag('link rel="icon" type="image/png" href="' . $site->favicon_path() . '"');
	}
	
	echo '<link rel="alternate" type="application/rss+xml" title="Ducati League" href="/rss/" />';
	
	
	echo '	<title>';
	// display the title specified in $page_title
	// the latter is defined in the script that includes this file
	if (isset($display_page_title) && (!(strcmp($display_page_title, '') === 0)))
	{
		echo strip_tags($display_page_title);
	} else
	{
		echo 'No page title set';
	}
	echo '</title>' . "\n";

?>
<link rel="shortcut icon" href="/styles/favicon.ico" />

<script src='/js/jquery-1.4.2.min.js' type='text/javascript'></script>
</head>
<body>
