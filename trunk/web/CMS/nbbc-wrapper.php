<?php
	class wrapper
	{
		function Parse($string)
		{
			require_once((dirname(__FILE__)) . '/nbbc-1.4.5/nbbc.php');
			$setup = new BBCode;
			$setup->SetSmileyURL(baseaddress() . 'smileys');
//			$setup->SetEnableSmileys(false);
			
			return $setup->Parse($string);
		}
    }
?>