<?php
	function active_login_modules()
	{
		// enable bzbb login and local login
		// the local login is only used to convert old users to bzbb users
		return array('bzbb' => 1,'local' => 1);
	}
?>