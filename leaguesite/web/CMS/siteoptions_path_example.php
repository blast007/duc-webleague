<?php
	// this is only a siteoptions_path example file
	
	// copy it to the path specified within siteinfo.php and
	// edit the return values appropriately to get the site running
	// do the same for the siteoptions example
	
	// __FILE__ is a PHP 5 constant that points to the current file (in this case siteinfo.php)
	// dirname(__FILE__) gets the directory name where this file resides (CMS/)
	// the other dirname's work like ../ in the shell.
	// this example points to ../../siteoptions.php
	// you could also use an absolute path
	// 	require_once ('/absolute/path/to/siteoptions.php');
	require_once (realpath(dirname(dirname(dirname(__FILE__))) . '/siteoptions.php'));
?>