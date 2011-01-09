<?php
ini_set ('session.use_trans_sid', 0);
ini_set ('session.name', 'SID');
ini_set('session.gc_maxlifetime', '7200');
@session_start();
header("Expires: Sat, 05 Nov 2005 00:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: text/xml; charset=UTF-8");

/*
Copyright &copy; 2008 Pippa http://www.spacegirlpippa.co.uk
Contact: sthlm.pippa@gmail.com

This file is part of wTag mini chat - shoutbox.

wTag is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

wTag is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wTag.  If not, see <http://www.gnu.org/licenses/>.
*/

// Configuration file is required.
require_once("conf.php");


// Check if the fields are not empty
// Check if name, url or message are not longer than the maximum length allowed
// For security and spam protection reasons check if $_POST['token'] has the same value as $_SESSION['token']
if (isset($_SESSION['username'])
    && ((isset($_POST['message']))
    && (trim($_POST['message']) !== "" )
    && (trim($_POST['message']) !== "message" )
    && (strlen($_POST['message']) < 400))
    && (isset($_SESSION['token'])
    && $_POST['token'] == $_SESSION['token'])
    ) {

    $name = $_SESSION['username'];
    
    $msg=$_POST['message'];
 

    // Get a sender IP (it will be in use in the next wTag version)
    $remote = $_SERVER["REMOTE_ADDR"];
    // Store it converted
    $converted_address=ip2long($remote);
   
    
    if (get_magic_quotes_gpc()) {
     
    $name = mysql_real_escape_string(stripslashes($name));
    $msg = mysql_real_escape_string(stripslashes($msg));
    
    }
    
    else {
     
    $name = mysql_real_escape_string($name);
    $msg = mysql_real_escape_string($msg);
      
    }
	date_default_timezone_set($site->used_timezone());
    $timestamp = date('Y-m-d H:i:s');
    // Insert a new message into database
    $sql->query("INSERT INTO wtagshoutbox SET name= '$name', message= '$msg', ip='$converted_address', date='$timestamp'");

    // Get the id for the last inserted message
    $lastid = $sql->get_id();
   
    // Delete oldest messages
    if ($lastid > 300) {
	
    $sql->query("DELETE FROM wtagshoutbox WHERE messageid <($lastid-20)");
    
    }

    // Retrieve last 20 messages
    $sql->query("SELECT date, name, url, message FROM wtagshoutbox WHERE messageid <= $lastid ORDER BY messageid DESC LIMIT 20");

    }

    else

    {
    // Just retrieve last 20 messages
    $sql->query("SELECT date, name, url, message FROM wtagshoutbox ORDER BY messageid DESC LIMIT 20");
       
    }


include_once("response.php");
?>