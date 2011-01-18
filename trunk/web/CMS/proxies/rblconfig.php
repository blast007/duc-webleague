<?php
$rblcfg['dbserver'] = 'localhost';
$rblcfg['dbuser'] =  $site->mysqluser(); 
$rblcfg['dbpassword'] =  $site->mysqlpw(); 
$rblcfg['db'] =  $site->db_used_name();

define(BLACKLISTED_USE_TEMPLATE, "blacklisted.html");	// Use this defined template to show error messages

// Unmark this row to redirect blacklisted users directly to the DNSBL-scanner
// define(BLACKLISTED_REDIRECT, "http://dnsbl.tornevall.org/scan.php");

// Use this if you really needs to add a prefix somewhere.
define('RBL_TABLE_PREFIX', '');		// If you need a prefix, add it here
define('RBL_ENABLE', true);		// Emergency! Set to false if you must turn this script off!
define('RBL_DELAY', 15);		// Time in minutes to store cached ips

// Proxy blocking
define('RBL_ANYTHING', false);		// Block anything that shows up as listed (not recommended)

// All settings are the defaults, which means they are the prefered settings for you.
// If you need more strength, change those settings to TRUE or FALSE
$rblprefs = array(
	"Tabuse" => TRUE,		// TornevallRBL: Block on "abuse"
	"Tanonymous" => TRUE,		// TornevallRBL: Block on anonymous access (anonymizers, TOR, etc)
	"Tblitzed" => TRUE,		// TornevallRBL: Block if host are found in the Blitzed RBL (R.I.P)
	"Tchecked" => FALSE,		// TornevallRBL: Block anything that has been checked
	"Telite" => TRUE,		// TornevallRBL: Block elite proxies (proxies with high anonymity)
	"Terror" => FALSE,		// TornevallRBL: Block proxies that has been tested but failed
	"Ttimeout" => FALSE,		// TornevallRBL: Block proxies that has been tested but timed out
	"Tworking" => TRUE,		// TornevallRBL: Block proxies that has been tested and works
	"Eopenproxy" => TRUE,		// EFNet: Block open proxies registered at rbl.efnet.org
	"Espamtrap50" => FALSE,		// EFNet: Block trojan spreading client (IRC-based)
	"Espamtrap666" => FALSE,	// EFNet: Block known trojan infected/spreading client (IRC-based)
	"Etor" => TRUE,			// EFNet: Block TOR Proxies
	"Edrones" => FALSE,		// EFNet: Drones/Flooding (IRC-based)
	"Ndialup" => FALSE,		// Njabl: Block dialups/dynamic ip-ranges (Be careful!)
	"Nformmail" => TRUE,		// Njabl: Block systems with insecure scripts that make them into open relays
	"Nmulti" => FALSE,		// Njabl: Block multi-stage open relays (Don't know what this is? Leave it alone)
	"Nopenproxy" => TRUE,		// Njabl: Block open proxy servers
	"Npassive" => FALSE,		// Njabl: Block passively detected "bad hosts" (Don't know what this is? Leave it alone)
	"Nrelay" => FALSE,		// Njabl: Block open relays (as in e-mail-open-relays - be careful)
	"Nspam" => FALSE		// Njabl: lock spam sources (Again, as in e-mail
);

// Other RBLs to use - Add them at your own risk in the array below!
/*
cbl.abuseat.org
dnsbl.njabl.org
dnsbl.ahbl.org
rbl.efnet.org
http.dnsbl.sorbs.net
socks.dnsbl.sorbs.net
misc.dnsbl.sorbs.net
*/

// RBL's to use
$opmlist = array(
	'opm.tornevall.org', 'dnsbl.njabl.org', 'rbl.efnet.org'
	);

// Exclude those from detection...
$exclude = array(
	'127.0.0.1'
        );

?>
