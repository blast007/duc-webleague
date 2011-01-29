<?php 


function createServer()
{
	global $site;
	global $connection;
		
	// sanitise variables
		
	if (isset($_POST['servername']))
	{
		$servername = $_POST['servername'];
	}	
	
	if (isset($_POST['serveraddress']))
	{
		$serveraddress = $_POST['serveraddress'];
	}	
	
	if (isset($_POST['type']))
	{
		$type = $_POST['type'];
	}	
	
	$owner = '';
	if (isset($_POST['owner']))
	{
		$owner = $_POST['owner'];
	}	
	
	$description = '';
	if (isset($_POST['description']))
	{
		$description = $_POST['description'];
	}
		
	$ALLOK = true;
	
	if (!isset($serveraddress) || $serveraddress === '')
	{
		echo '<p>Please enter the address of the server</p>' . "\n";
		$ALLOK = false;		
	} 
	
	if (!isset($servername) || $servername === '')
	{
		echo '<p>Please enter the name of the server</p>' . "\n";
		$ALLOK = false;		
	} 

	if (!isset($type) && ($type != 'match') && ($type != 'public') && ($type != 'replay')  )
	{
		echo '<p>Please choose correct type</p>' . "\n";
		$ALLOK = false;
	} 
	
	if (!$ALLOK) return;
	
	$query = ('INSERT INTO `servertracker` (`servername`, `serveraddress` '
			  . ', `description`, `owner`, `type`, `cur_players_total`)'
			  . ' VALUES (' . sqlSafeStringQuotes($servername) . ',' . sqlSafeStringQuotes($serveraddress)
			  . ',' . sqlSafeStringQuotes($description) . ',' . sqlSafeStringQuotes($owner)
			  . ',' . sqlSafeStringQuotes($type) . ',0)');
	
	if (!($result = $site->execute_query('servertracker', $query, $connection)))
	{
		$site->dieAndEndPage('The server could not be added due to a sql problem!');
	}

}



function deleteServer($id)
{
	global $site;
	global $connection;
	
	$query = ('DELETE FROM `servertracker` WHERE `id` =' . sqlSafeStringQuotes($id));
		
	if (!($result = $site->execute_query('servertracker', $query, $connection)))
	{
		$site->dieAndEndPage('The server could not be deleted due to a sql problem!');
	}	
}


function showServersList($allow_manage_polls)
{	
	global $site;
	global $connection;
	
	$query = ('SELECT * FROM servertracker');
			
	if (!($result = @$site->execute_query('servertracker', $query, $connection)))
	{
		$site->dieAndEndPageNoBox('The list of servers could not be displayed because of an SQL/database connectivity problem.');
	}
	
	$rows = (int) mysql_num_rows($result);
	
	echo '<form enctype="application/x-www-form-urlencoded" method="post" action="./edit.php" class="simpleform serveraddform">' . "\n";
	
	// input string
	echo '<div>';
	echo '<div class="formrow"><label for="servername">Server name:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="Server name" id="servername" name="servername" value=""');
	echo '</span></div> ' . "\n";
	echo '<div class="formrow"><label for="serveraddress">Server address:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="Server address" id="serveraddress" name="serveraddress" value=""');
	echo '</span></div> ' . "\n";
	echo '<div class="formrow"><label for="description">Description:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="Server description (like country)" id="description" name="description" value=""');
	echo '</span></div> ' . "\n";
	echo '</div> ' . "\n";
	echo '<div class="formrow"><label for="owner">Owner:</label> ' . "\n";
	echo '<span>';
		$site->write_self_closing_tag('input type="text" title="Server owner" id="owner" name="owner" value=""');
	echo '</span></div> ' . "\n";
	
	echo '<div class="formrow"><label for="type">Type:</label> ' . "\n";
	echo '<span><select name="type" id="type">';
		echo '<option value="match">Match</option>';
		echo '<option value="public">Public</option>';
		echo '<option value="replay">Replay</option>';
	echo '</select></span></div> ' . "\n";
	
	echo '<div class="formrow">';
	$site->write_self_closing_tag('input type="submit" name="create" value="Add new server" id="send" class="button"');
	echo '</div>' . "\n";
	echo '</form>' . "\n";
	
	
	if ($rows === (int) 0)
	{
		echo '<p class="message">No active servers.</p>' . "\n";
		$site->dieAndEndPageNoBox();
	}
	unset($rows);
	
	
	
	
	echo '<table id="shouts" class="big">' . "\n";
	echo '<thead><tr>' . "\n";
	echo '	<th>Name</th>' . "\n";
	echo '	<th>Address</th>' . "\n";
	echo '	<th>Description</th>' . "\n";
	echo '	<th>Owner</th>' . "\n";
	echo '	<th>Type</th>' . "\n";
	echo '	<th>Actions</th>' . "\n";
	echo '</tr></thead>' . "\n\n";
	
	// display message overview
	$results_list = Array (Array ());
	// read each entry, row by row
	while ($row = mysql_fetch_array($result))
	{
		$id = (int) $row['id'];
		$results_list[$id]['servername'] = $row['servername'];
		$results_list[$id]['serveraddress'] = $row['serveraddress'];
		$results_list[$id]['description'] = $row['description'];
		$results_list[$id]['owner'] = $row['owner'];
		$results_list[$id]['type'] = $row['type'];
		$results_list[$id]['id'] = $row['id'];		
	}
	unset($results_list[0]);
	
	// query result no longer needed
	mysql_free_result($result);
	
		
	// walk through the array values
	foreach($results_list as $result_entry)
	{
		echo '<tr>' . "\n";
		echo '<td>' . $result_entry['servername'] . '</td>' . "\n";
		echo '<td>' . $result_entry['serveraddress'] . '</td>' . "\n";
		echo '<td>' . $result_entry['description'] . '</td>' . "\n";
		echo '<td>' . $result_entry['owner'] . '</td>' . "\n";
		echo '<td>' . $result_entry['type'] . '</td>' . "\n";
		
		// show allowed actions based on permissions
		if ($allow_manage_polls)
		{
			echo '<td>';
			echo '<a class="button" href="./edit.php?delete=' . htmlspecialchars($result_entry['id']) . '" 
			onclick="return confirm(\'Are you sure you want to delete this server?\')" >Delete</a> ';
			echo '</td>' . "\n";
		}
		
		echo '</tr>' . "\n\n";
			}
	unset($results_list);
	unset($result_entry);
	
	
	// no more matches to display
	echo '</table>' . "\n";
	
}

?>