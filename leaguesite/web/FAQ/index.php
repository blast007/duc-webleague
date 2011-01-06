<?php
	// the current page to be edited
	$page_title = 'FAQ/';
	$display_page_title = 'FAQ';
	$cmspage = 'faq';
	
	$randomkey_name = ('randomkey_static_pages_' . $page_title);
	
	// do not allow editing messages
	$entry_edit_permission = 'allow_edit_static_pages';
	
	include_once('../CMS/announcements/static_website_content.php');
?>