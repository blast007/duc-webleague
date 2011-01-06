<?php
	// set a cookie to test if client accepts cookies
	$output_buffer = '';
	ob_start();
	require realpath('../CMS/siteinfo.php');
	@setcookie('cookies', "allowed", 0, basepath() . 'Config/', domain(), 0);
	
	$stylesheet = '';
	if (isset($_GET['stylesheet']))
	{
		$stylesheet=$_GET['stylesheet'];
	}
	
	if (strlen($stylesheet) > 0)
	{
		$cookies = false;
		// if script is called again (content in $stylesheet), one can test if cookies are activated
		foreach ($_COOKIE as $key => $value)
		{
			if (strcasecmp($key, 'cookies') == 0)
			{
				// cookies are activated
				$cookies = true;
			}
		}
		if ($cookies == false)
		{
			// cookies are not allowed -> use SIDs with GET
			// SIDs are used elsewhere only for permission system
			ini_set ('session.use_trans_sid', 1);
			$_SESSION['stylesheet'] = $stylesheet;
		} else
		{
			ini_set ('session.use_trans_sid', 0);
			@setcookie('stylesheet', $stylesheet, time()+60*60*24*30, basepath(), domain(), 0);
		}
	}
	
	ini_set ('session.name', 'SID');
	ini_set('session.gc_maxlifetime', '7200');
	session_start();
	
	$output_buffer .= ob_get_contents();
	ob_end_clean();
	// write output buffer
	echo $output_buffer;
	
	require_once '../CMS/siteinfo.php';
	$site = new siteinfo();
	
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
	echo '	' . ($site->write_self_closing_tag('meta content="text/html; charset=utf-8" http-equiv="content-type"')) . "\n";
	
	if (strlen($stylesheet) > 0)
	{
		// we have a new stylesheet chosen by user
		echo '  <link href="../styles/';
		echo $stylesheet;
		echo '.css" rel="stylesheet" type="text/css">' . "\n";
	} else
	{
		// use previously used stylesheet
		include '../stylesheet.inc';
	}
	
	if (!(strcmp($site->favicon_path(), '') === 0))
	{
		echo '	';
		echo $site->write_self_closing_tag('link rel="icon" type="image/png" href="' . $site->favicon_path() . '"');
	}
?>
<title>Config</title>
</head>
<body>
<?php
	require realpath('../CMS/navi.inc');
?>
<h1 class="tools">Config</h1>

<div class="static_page_box">
<p class="first_p">This is the user configuration section.</p>
<?php
	// allow turning on or off SQL debug output
	if (isset($_SESSION['allow_change_debug_sql']) && $_SESSION['allow_change_debug_sql'])
	{
		// $site has been instantiated in navi.inc
		if ($site->debug_sql())
		{
			// SQL debuggin currently on
			
			if (isset($_GET['debug']))
			{
				if ((int) $_GET['debug'] === 0)
				{
					// user wishes to turn off SQL debugging
					echo '<a href=".?debug=1">Turn on SQL debugging this session</a>' . "\n";
					$_SESSION['debug_sql'] = false;
				} else
				{
					// user wishes to turn on SQL debugging
					echo '<a href=".?debug=0">Turn off SQL debugging this session</a>' . "\n";
					$_SESSION['debug_sql'] = true;
				}
			} else
			{
				echo '<a href=".?debug=0">Turn off SQL debugging this session</a>' . "\n";
			}
		} else
		{
			// SQL debuggin currently off
			
			if (isset($_GET['debug']))
			{
				if ((int) $_GET['debug'] === 0)
				{
					echo htmlent((int) $_GET['debug']);
					// user wishes to turn off SQL debugging
					echo '<a href=".?debug=1">Turn on SQL debugging this session</a>' . "\n";
					$_SESSION['debug_sql'] = false;
				} else
				{
					// user wishes to turn on SQL debugging
					echo '<a href=".?debug=0">Turn off SQL debugging this session</a>' . "\n";
					$_SESSION['debug_sql'] = true;
				}
			} else
			{
				echo '<a href=".?debug=1">Turn on SQL debugging this session</a>' . "\n";
			}
		}
	}
?>
<form enctype="application/x-www-form-urlencoded" method="get" action="<?php
	
	// the address depends on where the file resides
	$url = baseaddress() . 'Config/';
	echo $url;
?>">
<p>Theme:
	<select name="stylesheet">
<?php
	$styles = array('Ducati');
	
	foreach ($styles AS $s) {
		echo '<option value="'.$s.'"'.($stylesheet==$s?' selected="selected"':'').'>'.urldecode($s)."</option>\n";
	}
	echo "</select>\n";
	
	$site->write_self_closing_tag('input type="submit" value="Submit changes" class="button"');
?>
</p>
</form>

<?php
	if ((isset($_SESSION['allow_view_todo'])) && ($_SESSION['allow_view_todo']))
	{
		echo '<a href="../TODO/">View TODO</a>';
	}
?>

</div>
<?php
	if (file_exists('../.svn/entries'))
	{
		$handle = fopen('../.svn/entries', 'rb');
		$counter = 1;
		while ($rev = fscanf($handle, "%[a-zA-Z0-9,. ]%[dir]\n%[a-zA-Z0-9,.]"))
		{
			$counter++;
			
			if ($counter > 4)
			{
				// Listing some of them
				list($svn_rev) = $rev;
				echo '<p>SVN revision: ' . $svn_rev . '</p>' . "\n";
				break;
			}
		}
		fclose($handle);
		unset($counter);
	}
?>
</div>
</body>
</html>
