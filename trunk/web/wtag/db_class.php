<?php
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
class Sql {
          
var $conn; 
var $host;
var $user;
var $pass;
var $db;
var $result;
var $lastid;
        
        
	
// Constructor
function Sql($host, $user, $pass, $db) {
    
    $this->host=$host;
    $this->user=$user;
    $this->pass=$pass;
    $this->db=$db;
    $this->conn = @mysql_connect($this->host, $this->user, $this->pass);
    @mysql_select_db($this->db, $this->conn);
    
} 


// Perform query
function query($query) {
    
    if(!$this->result = @mysql_query($query, $this->conn)) {
    return false;
    }
    
    else
    return true;
	
}


// Count rows
function count_rows($result) {
		
    return @mysql_num_rows($this->result);
	
}


// Fetch row
function fetch_row() {
		
    return @mysql_fetch_array($this->result, MYSQL_ASSOC);
	
}
	
	
// Get the id for the last inserted row
function get_id() {
   
    $this->lastid = @mysql_insert_id($this->conn);
    return  $this->lastid;
	
}

//End of the class
}
?>