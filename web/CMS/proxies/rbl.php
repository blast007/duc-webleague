<?php

/***

 RBL Extension for What Ever You Need It For - 1.0.2
 T. Tornevall 2008-01-27 [thorne@tornevall.net]

	Please read the README

***/

// Compatibility - edit your configuration to make yourself fully compatible with future stuff.

if (!defined('RBL_DIRECTORY')) {define('RBL_DIRECTORY', '');}

if ($_GET['self'])
{
	highlight_file('rbl.php');
	exit;
}

if (file_exists(RBL_DIRECTORY . '/rblconfig.php'))
{
	require_once(RBL_DIRECTORY . '/rblconfig.php');
	if (!is_array($rblcfg))
	{
		die("Whoa! Still no configuration!");	// Prevent this script from running unless you have the config...
	}
}
else
{
	die("Whoa! Configuration missing.");	// Do not run unless the config really exists
}

define('TIMENOW', time());

// Here goes the stuff that you need to make this script independent.
class RBL_DB
{

	function db ($dbname = '')
	{
		global $rbldb, $rbl;
		if (!$rbldb) {$this->connect();}
		if (!$dbname) {break;}
		mysql_select_db(addslashes($dbname), $rbldb);
	}

	function connect ()
	{
		global $rblcfg, $rbldb, $rbl;
                // db-connections outside vBulletin are made here
		$server = $rblcfg['dbserver'];
		$user = $rblcfg['dbuser'];
		$pass = $rblcfg['dbpassword'];
		$rbldb = mysql_connect($server, $user, $pass);
		if (!$rblcfg['db']) {die("Oops. No database set?");}
		$this->db($rblcfg['db']);
		unset($rbl);	// Unset this cfg after load, just in case
		if (!$rblcfg) {die('Could not connect: ' . mysql_error());}
	}

	function fetch($Array)
	{
		global $rbldb, $tornevalldb;
		if (!$rbldb) {$this->connect();}
		if (!$rbldb) {return;}
		if (!mysql_errno($rbldb))
		{
			return mysql_fetch_array($Array, MYSQL_ASSOC);
		}
		else
		{
			return array();		// Return nothing on errors
		}
	}

	function query($query)
	{
		global $rbldb;
		if (!$rbldb) {$this->connect();$isconnect = true;}
		if (!$rbldb AND !$isconnect) {return;}
		$tSQL = mysql_query($query, $rbldb);
		return $tSQL;
	}

	// Fetch the first row found...
	function query_first($query)
	{
		global $rbldb;
		if (!$rbldb) {$this->connect();}
		if (!$rbldb) {return;}
		$tSQL = mysql_query($query, $rbldb);
		$returnthis = $this->fetch($tSQL);
		return $returnthis;
	}

	// Protect your database here...
	function injection($value)
	{
		if (get_magic_quotes_gpc())
		{
			$value = stripslashes($value);
		}
		if (!is_numeric($value))
		{
			//$value = "'" . mysql_real_escape_string($value) . "'";
			$value = mysql_real_escape_string($value);
		}
		return $value;
	}

}

// Initialize!
$rbl =& new RBL_DB;
$rbl->connect();		// Connect all the way!
global $rbldb;

// Self installing crap. on failure, contact your system admin - not me, since this really SHOULD work if
// your permissions are correctly set...
$findtable = $rbl->query_first("SELECT * FROM rbl_config WHERE param = 'init'");
if (!$findtable['param'])
{
	$rbl->query("INSERT INTO rbl_config (param, value) VALUES ('init', '".TIMENOW."')");
	$exist = mysql_affected_rows($rbldb);
	if ($exist < 0)
	{
		$createquery1 = ("
			CREATE TABLE ".RBL_TABLE_PREFIX."rbl_config (
				`param` varchar(32) NOT NULL,
				`value` varchar(64) NOT NULL default '',
				PRIMARY KEY (`param`),
				KEY rbl_config (`param`,`value`)
				)");
		$createquery2 = ("
				CREATE TABLE ".RBL_TABLE_PREFIX."rbl_cache (
				rblid INTEGER NOT NULL AUTO_INCREMENT,
				ip VARCHAR(40) NOT NULL,
				listed INTEGER,
				dateline INTEGER,
				PRIMARY KEY (`rblid`),
				INDEX `blacklist`(`ip`, `listed`, `dateline`)
				)");
		$rbl->query($createquery1);
		$rbl->query($createquery2);
		$rbl->query("INSERT INTO rbl_config (param, value) VALUES ('init', '".TIMENOW."')");
		$exist = mysql_affected_rows($rbldb);
		if ($exist < 0) {die("There seem to be a problem with your database. Contact someone to have this problem fixed and try again.");}
	}
}


// Arrayed bitmasking - begin
$rtornevall = array(
	tornevall_checked => 1,
	tornevall_working => 2,
	tornevall_blitzed => 4,
	tornevall_timeout => 8,
	tornevall_error => 16,
	tornevall_elite => 32,
	tornevall_abuse => 64,
	tornevall_anonymous => 128
);

$rnjabl = array(
	njabl_relay => 2,
	njabl_dialup => 3,
	njabl_spam => 4,
	njabl_multi => 5,
	njabl_passive => 6,
	njabl_formmail => 8,
	njabl_openproxy => 9
);

$refnet = array(
	efnet_openproxy => 1,
	efnet_spamtrap666 => 2,
	efnet_spamtrap50 => 3,
	efnet_tor => 4,
	efnet_drones => 5
);
// Arrayed bitmasking - finish

function bitmask ($bit = '')
{
	$loadbits = 8;
	for ($i = 0 ; $i < $loadbits ; ++$i) {$arr[] = pow(2,$i);}
	for ($i = 0 ; $i < count($arr) ; ++$i) {$mask[$i] = ($bit & $arr[$i]) ? '1' : '0';}
	return $mask;
}

function rblresolve ($ip = '', $rbldomain = '')
{
	if (!$ip) {return false;}                       // No data should return nothing
	if (!$rbldomain) {return false;}        // No rbl = ignore
	$returnthis = explode('.', gethostbyname(implode('.', array_reverse(explode('.', $ip))) . '.' . $rbldomain));           // Not ipv6-compatible!
	// 127-bug-checking
	if (implode(".", $returnthis) != implode('.', array_reverse(explode('.', $ip))) . '.' . $rbldomain) {return $returnthis;} else {return false;}
} 

function template ($filename = '')
{
	global $site, $phrase, $template;
	if (!$filename OR !file_exists($filename) OR !filesize($filename)) {return;}
	if (preg_match("[^/]", $filename,$m)) {return;}	// Don't do this
	$thandle = fopen($filename, 'r');
	$retdata = fread($thandle, filesize($filename));
	$retdata = addslashes($retdata);
	$retdata = str_replace("\\'", "'", $retdata);
	fclose($thandle);
	return $retdata;
}


// Run this script only if asked to...
if (defined('RBL_ENABLE'))
{
	// Clean up cache
	$rbldelay = intval(TIMENOW-(RBL_DELAY*60));
	$rbl->query("DELETE FROM ".RBL_TABLE_PREFIX."rbl_cache WHERE dateline < $rbldelay");

	// Tornevall DNSBL for vBulletin (Borrowed! Thanks, me!)

	$block = array();
	foreach ($rblprefs as $option => $value)
	{
		$findrbltype = substr($option, 0, 1);
		if ($findrbltype == strtoupper($findrbltype))
		{
			$setting = substr($option, 1);

			// Supported proxylists
			if ($findrbltype == "T") {$setrbltype = "tornevall_";}
			if ($findrbltype == "N") {$setrbltype = "njabl_";}
			if ($findrbltype == "E") {$setrbltype = "efnet_";}
			$block["$setrbltype$setting"] = $value;
		}
	}

	if (defined('RBL_ANYTHING')) {$block['everything'] = RBL_ANYTHING;}

	$OPMremote = $_SERVER['REMOTE_ADDR'];
	// Do we have this host registered in our cache?
	$proxydb = $rbl->query_first("SELECT ip,listed FROM " . RBL_TABLE_PREFIX . "rbl_cache WHERE ip = '$OPMremote' LIMIT 1");
	$proxyexist = $proxydb['ip'];
	$proxylisted = $proxydb['listed'];

	// Already listed means blocking...
	if ($proxylisted > 0) 
	{
		$opmeverything = 1;
		$blockremote = 1;
	}

	// If nothing exist in cache, do a lookup
	if (!$proxyexist)
	{
		// Collect data...
		foreach ($opmlist as $OPM)
		{
			$rblresponse = rblresolve($OPMremote, $OPM);
			if ($rblresponse[0] == '127')
			{
				$opmfound[$OPM]['result'] = $rblresponse[3];
				$opmeverything = 1;	// Want to block anything? Mark your wishes here
			}
		}
	}

	// If the proxy is'nt listed, let's analyze it's data
	if ($proxylisted < 1)
	{
		// Functions for special blocking
		foreach ($opmlist as $OPM)
		{
			// Blocking based on opm.tornevall.org settings start
			if (preg_match("[tornevall.org]", $OPM, $matches))
			{
				foreach ($rtornevall as $OPM_t => $OPM_tc)
				{
					$bit = ((($opmfound[$OPM]['result'] & $OPM_tc) == 0) ? '' : $OPM_t);
					if ($bit) {if ($block["$OPM_t"] == 1) {$blockremote = $opmfound[$OPM]['result'];}}
				}
			}
			// Blocking based on opm.tornevall.org settings stop

			// Blocking based on njabl.org settings start
			if (preg_match("[njabl.org]", $OPM, $matches))
			{
				foreach ($rnjabl as $njcheck => $value)
				{
					if ($opmfound[$OPM]['result'] == $value && $block[$njcheck] > 0) {$blockremote = $opmfound[$OPM]['result'];}
				}
			}
			// Blocking based on njabl.org settings stop

			// Blocking based on efnet.org settings start
			if (preg_match("[efnet.org]", $OPM, $matches))
			{
				foreach ($refnet as $efcheck => $value) 
				{
					if ($opmfound[$OPM]['result'] == $value && $block[$efcheck] > 0) {$blockremote = $opmfound[$OPM]['result'];}
				}
			}
			// Blocking based on efnet.org settings stop
		}
	}

	// If we choose to block anything listed, it's time to do that now
	if ($opmeverything > 0 && $block['everything'] > 0) {$blockremote = 1;}

	// If we get a positive value from our scan (or a negative which means that everything should be blocked)
	// we will add the proxy to the cache so we can avoid too many scans to the dns

	// Reset on exclusions
	foreach ($exclude as $ipexclude)
	{
		if ($ipexclude == $_SERVER['REMOTE_ADDR']) {unset($blockremote, $proxyexist);}
	}

	if ($blockremote)
	{
		// Add an entry if it's not there
		if (!$proxyexist)
		{
			$rbl->query("INSERT INTO " . RBL_TABLE_PREFIX . "rbl_cache (ip, listed, dateline) VALUES ('$OPMremote', 1, ".TIMENOW.")");
		}
	}
	else
	{
		// Is the remoted listed to the cache? No? Ok, add it as unlisted
		if (!$proxyexist) {$rbl->query("INSERT INTO " . RBL_TABLE_PREFIX . "rbl_cache (ip, listed, dateline) VALUES ('$OPMremote', 0, ".TIMENOW.")");}
	}

	if ($blockremote)
	{
		require_once '../CMS/navi.inc';
		echo '<div class="static_page_box">' . "\n";
		echo '		
		<h3>403: Permission Denied - Proxy Error</h3>
		<hr />
		The system administrator on this site is currently using a proxy-blocker and you (' . $_SERVER['REMOTE_ADDR'] . ') are blacklisted.<br />
		<br />
		If you want to know more about this blacklist, you may want use the <b>free <a href="http://dnsbl.tornevall.org/scan.php">proxy scanner</a></b> at dnsbl.tornevall.org.<br />
		<br />
		<hr />
		<b><font color="#FF0000">WARNING</font></b><br />
		<br />
		You should consider NOT to contact the owner of the site above about anything regarding IP-removal.<br />
		The database shown there is based on <i>other</i> databases on the Big Internet, that is<br />
		<b>not</b> stored locally. If you are blacklisted, it is not the owners fault - it is probably someone<br />
		elses.<br />';
		
		echo 'Internal Error: Proxy Error!<br /><a href="http://dnsbl.tornevall.org/scan.php">Look here to see why.</a>';
		
		$site->dieAndEndPage($error_msg);
		
	}
}

?>
