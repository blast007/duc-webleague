<?php
include('bzfquery.php');
require_once '../CMS/siteinfo.php';

$langs = array();

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    // break up string into pieces (languages and q factors)
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

    if (count($lang_parse[1])) {
        // create a list like "en" => 0.8
        $langs = array_combine($lang_parse[1], $lang_parse[4]);
    	
        // set default to 1 for any without q factor
        foreach ($langs as $lang => $val) {
            if ($val === '') $langs[$lang] = 1;
        }

        // sort list based on value	
        arsort($langs, SORT_NUMERIC);
    }
}

$treffer = 0;
if (isset($_GET['lang']))
{
    setzeSprache($_GET['lang']);
    $treffer = 1;
}

if ($treffer == 0)
{
    // look through sorted list and use first one that matches our languages
    foreach ($langs as $lang => $val) {
        if ((strpos($lang, 'de') === 0))
        {
            // show German site
            setzeSprache('de');
            $treffer = 1;
            break;
        } else if (strpos($lang, 'en') === 0) {
            // show English site
            setzeSprache('en');
            $treffer = 1;
            break;
        }
    }
}

if ($treffer == 0)
{
    setzeSprache('en');
}

// show default site or prompt for language
function setzeSprache($sprache)
{
    if ($sprache=='de')
    {
        define ('KEINESPIELER', 'Keine Spieler gefunden.');
        define ('KEINEVERBINDUNG', 'Keine Verbindung zum Server m&ouml;glich.');
        define ('GEMELDETERFEHLER', 'Der zugrunde liegende Prozess meldete einen Fehler: ');
        define ('ZAEHLER', 'Z&auml;hler l&auml;uft: ');
        define ('VON', ' von ');
        define ('RESTZEIT', ' Minuten verbleibend.');
    } else
    {
        define ('KEINESPIELER', 'No players found.');
        define ('KEINEVERBINDUNG', 'Could not establish a connection to the server.');
        define ('GEMELDETERFEHLER', 'The underlying process reported an error: ');
        define ('ZAEHLER', 'Countdown is running: ');
        define ('VON', ' of ');
        define ('RESTZEIT', ' minutes remain to be played.');
    }
}

function cmp($a, $b)
{
    // Zuschauer
    if (($a['team'] == 5) and ($b['team'] == 5))
    {
        return (strnatcasecmp($a['sign'], $b['sign']));
    } else
    {
        if ($a['team'] == 5)
        {
            return 1;
        }
        
        if ($b['team'] == 5)
        {
            return -1;
        }
    }
    
    // Normale Spieler
    $scoreA = ($a['won'] - $a['lost']);
    $scoreB = ($b['won'] - $b['lost']);
    if ($scoreA == $scoreB)
    {
        return 0;
    }
    return ($scoreA > $scoreB) ? -1 : 1;
}

function marke($markierung, $name)
{
    echo '<' . $markierung . ' class=' . "\x22" . $name . "\x22" . '>';
}

function formatshowserver($server,  $description = '')
{
	global $site;
	
	formatshowserver_last($server,  $description);
	$site->write_self_closing_tag('hr');
}


function formatshowserver_last($server,  $description = '')
{
	if (isset($_GET['server']))
    {
        echo '<p>' . $server . '<span class="description">' . $description . '</span></p>' . "\n";
    } else
    {
        echo '<p><a href="?server=' . urlencode($server) . '">' . $server . '</a>';
        if ($description != '') echo ' <span class="description">(' . $description . ')</span>';
        echo '</p>' . "\n";
    }
}


function formatbzfquery($server, $connection, $description)
{
	global $site;
	
	formatbzfquery_last($server, $connection,  $description);
	$site->write_self_closing_tag('hr');
}

function formatbzfquery_last($server, $connection,  $description = '')
{
	global $site;
	global $connection;
	global $use_internal_db;
    if ($use_internal_db)
	{
        @!mysql_select_db($site->db_used_name(), $connection);
    } else
	{
		if (@!mysql_select_db("playerlist", $connection))
		{
			@mysql_close($connection);
			unset($connection);
		}
    }
	
    if (isset($_GET['server']))
    {
        echo '<p>' . $server . '<span class="description">' . $description . '</span></p>' . "\n";
    } else
    {
        echo '<p><a href="?server=' . urlencode($server) . '">' . $server . '</a>';
        if ($description != '') echo ' <span class="description">(' . $description . ')</span>';
        echo '</p>' . "\n";
    }
    // Query the server
	if (!function_exists('pcntl_fork'))
	{
		ob_start();
	}
	$data = bzfquery($server);
    $ausgabe = '';
	if (!function_exists('pcntl_fork'))
	{
		$ausgabe .= ob_get_contents();
		ob_end_clean();
	}
	
    if (!isset($data['player']))
    { 
        if (!isset($data['protocol']))
		{
			echo '<p>' . KEINEVERBINDUNG . ' ';
			if (! strcmp($ausgabe, '') == 0)
			{
				echo GEMELDETERFEHLER . $ausgabe . '.';
			}
			echo '</p>' . "\n";
		} else
		{
			echo '<p>' . KEINESPIELER . '</p>' . "\n";
		}
    } else
    {
        $zaehler = $data['maxTime'] - $data['timeElapsed'];
        if ($zaehler > 0)
        {
            echo '<p class="zaehler">' . ZAEHLER
              . '<span class="zaehler">'
              . round(($zaehler / 60), 2) . VON . round((($data['maxTime']) /60), 2)
              . '</span>' . RESTZEIT . '</p>' . "\n";
        }
		
		// Display the server info
		$teamName = array(0=>"schurke", 1=>"rot", 2=>"gruen", 3=>"blau", 4=>"violett", 5=>"zuschauer", 6=>"hase");
		$teamColour = array(0=>"yellow", 1=>"red", 2=>"green", 3=>"blue", 4=>"purple", 5=>"gray", 6=>"orange");
		
		usort ($data['player'], "cmp");

        
        echo "\n\n" . '<table class="spieler" border="0">' . "\n";
        echo '  <tbody>';

        while (list($key, $val) = each($data['player']))
        {
            echo "\n" . '<tr>' . "\n";
            // Zuschauer spielen nicht -> keine Punktzahl
            if (! strcmp($teamName[$data['player'][$key]['team']], 'zuschauer') == 0)
            {
                echo '<td>';
                echo ($data['player'][$key]['won'] - $data['player'][$key]['lost']);
                echo '</td>' . "\n";
                echo  '<td>('
                  . $data['player'][$key]['won'] . '-'
                  . $data['player'][$key]['lost'] . ')</td><td>['
                  . $data['player'][$key]['tks'] . ']</td>';
            } else
            {
                echo '<td></td>' . "\n" . '<td></td>' . "\n" . '<td></td>' . "\n";
            }
            // Mannschaftsfarbe
            marke('td',$teamName[$data['player'][$key]['team']]);
			$playername = $data['player'][$key]['sign'];
			// Spielernamen eventuell kuerzen
			if ($site->mobile_version())
			{
				// Name ziemlich lang
				if (strlen($playername) > 13)
				{
					$playername = (str_split($playername,10));
					echo htmlent($playername[0]) . "...";
				} else // Name kurz genug
				{
					echo htmlent($playername);
				}
			} else // Vollversion, massig Platz auf Bildschirm vorhanden
			{
				echo htmlentities($playername);
			}
			echo '</td>' . "\n";
            // Mehl
            marke('td','mehl');
            if (! strcmp($data['player'][$key]['email'], '') == 0)
            {
				$email = $data['player'][$key]['email'];
				// Email ziemlich lang
				if (strlen($email) > 17)
				{
					$email = (str_split($email,14));
					$email = htmlent($email[0])  . '...';
				}
				echo '('
                  . htmlent($email)
                  . ')';
            }
            echo '</td>' . "\n";
            
            // Existiert Datenbankverbindung?
            if ($connection)
            {
                // team herausfinden
                marke('td', 'team');
                $callsign = $data['player'][$key]['sign'];
                $query = 'SELECT `teamid` from players WHERE `name`='. sqlSafeStringQuotes($callsign) . ' LIMIT 1';
                $result = mysql_query($query, $connection);
                if (!$result)
                {
                    print mysql_error();
                    die("<br>\nQuery $query ist ung&uuml;ltiges SQL.");
                }
                $resultarray = mysql_fetch_array($result);
                $teamid = $resultarray['teamid'];
                if ($teamid > 0)
                {
					if ($use_internal_db)
					{
						$query = 'SELECT `name` from teams WHERE `id`=' . sqlSafeStringQuotes($teamid) . ' LIMIT 1';
					} else
					{
						$query = 'SELECT `name` from teams WHERE `teamid`=' . sqlSafeStringQuotes($teamid) . ' LIMIT 1';
					}
                    $result = mysql_query($query, $connection);
                    if (!$result)
                    {
                        print mysql_error();
                        die("<br>\nQuery $query ist ung&uuml;ltiges SQL.");
                    }
                    $resultarray = mysql_fetch_array($result);
                    mysql_free_result($result);
                    echo $resultarray['name'];
                }
				echo '</td>' . "\n";
            }
            echo '</tr>' . "\n";
        }
        echo '  </tbody>' . "\n" . '</table>' . "\n";
        
        
        		
//		echo 'count punkte:!' . print_r($data['player']['0']['team']);
		if (isset($data['player']['0']['team']) && !(strcmp($data['player']['0']['team'], '5') === 0))
		{
			echo '<table class="punkte">' . "\n";
			echo '  <tbody>' . "\n";
			
			while (list($key, $val) = each($data['team']))
			{
				if ($data['team'][$key]['size'] > 0)
				{
					echo '    ';
					// Mannschaftsfarbe
					marke('tr',$teamName[$key]);
					// Punktzahl
					echo '<td>';
					echo ($data['team'][$key]['won'] - $data['team'][$key]['lost']);
					echo '</td>';
					// Gewonnen
					echo '<td>';
					echo '(' . $data['team'][$key]['won'] . ' - ';
					// Verloren
					echo $data['team'][$key]['lost'] . ')';
					echo '</td>';
					// #Spieler
					echo '<td>';
					echo $data['team'][$key]['size'];
					echo '</td>';
					// Ende Mannschaftsfarbe
					echo '</tr>' . "\n";
				}
			}
			echo '  </tbody>' . "\n" .'</table>' . "\n";
		}
		reset($data);
        
        
    }
}
	
//print_r($data['player']);
?>