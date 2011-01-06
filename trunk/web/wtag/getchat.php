<?php
header("Expires: Sat, 05 Nov 2005 00:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: text/xml; charset=UTF-8");

// Configuration file is required.
require_once("conf.php");

// Retrieve last 20 messages from database and order them in descending order 
$sql->query("SELECT date, name, message FROM wtagshoutbox ORDER BY messageid DESC LIMIT 20");

include_once("response.php");
?>
