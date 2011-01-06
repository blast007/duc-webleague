<?php
	// the current page to be edited
	// do not use dots and spaces as these are converted to underscores
	// this seems to be a legacy of register_globals = on
	// see also: http://us2.php.net/variables.external
	$page_title = '_/';
	$display_page_title = 'Welcome to the <span> Ducati League</span>';
	$cmspage = 'home';
	
	$randomkey_name = 'randomkey_static_pages_' . $page_title;
	//	$entry_add_permission = 'allow_add_messages';
	
	// do not allow editing messages
	$entry_edit_permission = 'allow_edit_static_pages';
	
	include_once('CMS/announcements/static_website_content.php');
?>