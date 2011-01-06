<?php
	// the current page to be edited
	$page_title = 'TODO/';
	$display_page_title = 'TODO';
	$cmspage = 'TODO';
	
	$randomkey_name = ('randomkey_static_pages_' . $page_title);
	
	// do not allow editing messages
	$entry_edit_permission = 'allow_edit_todo';
	
	include_once('../CMS/announcements/static_website_content.php');
?>