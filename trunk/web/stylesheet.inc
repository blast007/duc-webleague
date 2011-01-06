<?php
	if (!isset($site))
	{
		require_once 'CMS/siteinfo.php';
		$site = new siteinfo();
	}
	
	if ($site->mobile_version())
	{
		define("default_stylesheet", 'White');
	} else
	{
		define("default_stylesheet", 'Ducati');
	}
	
	$stylesheet = default_stylesheet;
	foreach ($_COOKIE as $key => $value)
	{
		if (strcasecmp($key, 'stylesheet') == 0)
		{
			// cookies turned on
			$stylesheet = $value;
		}
	}
	
	if (isset($_SESSION['stylesheet']))
	{
		$stylesheet = $_SESSION['stylesheet'];
	}
	
	if (!(file_exists(dirname(__FILE__) . '/styles/' . $stylesheet . '.css')))
	{
		// stylesheet in question does not exist, go back to default
		$stylesheet = default_stylesheet;
	}
	
	if (strcasecmp($stylesheet, '') == 0)
	{
		// nothing is set, go back to default
		$stylesheet = default_stylesheet;
	}
	
	//host and path
	$url = baseaddress();
	
	echo '	';
	$stylesheet_written = 'link href="' . $url;
	// Need to append file extension as url
	// apache allows not using it by default
	$stylesheet_written .= 'styles/' . str_replace(' ', '%20', htmlspecialchars($stylesheet)) . '.css"';
	$stylesheet_written .= ' rel="stylesheet" type="text/css"';
	
	$site->write_self_closing_tag($stylesheet_written);
	unset($stylesheet_written);
?>
