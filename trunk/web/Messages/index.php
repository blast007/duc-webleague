<?php
	// find out which folder should be displayed
	$folder = '';
	if (isset($_GET['folder']))
	{
		$folder = $_GET['folder'];
	}
	
	// set default folder to inbox
	if (strcmp($folder, '') === 0)
	{
		$folder = 'inbox';
	}
	// if the folder is not inbox then it is outbox
	if (!(strcmp($folder, 'inbox') === 0))
	{
		$folder = 'outbox';
	}
	
	$recipient = '';
	if (isset($_GET['to']))
	{
		$recipient = $_GET['to'];
	}
	$allow_different_timestamp = false;
		
	// write private messages instead of announcements
	$message_mode = true;
	$table_name = 'messages_storage';
	$table_name_msg_user_connection = 'messages_users_connection';
	
	// do not sent private messages as another user
	$author_change_allowed = '';
	$randomkey_name = 'randomkey_messages';
	$entry_add_permission = 'allow_add_messages';
	
	// do not allow editing messages
	$entry_edit_permission = 'allow_edit_messages';
	$entry_delete_permission = 'allow_delete_messages';
	
	include_once('../CMS/announcements/index.php');
?>