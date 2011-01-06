<?
require_once 'siteinfo.php';

define ('MYPAGE', baseaddress() . 'CMS/');
define ('AUTH_LINK', "http://my.bzflag.org/weblogin.php?action=weblogin&url=". MYPAGE ."?bzbbauth=%25TOKEN%25,%25USERNAME%25");
$ALLOWED_GROUPS =  array ('ducati.admins', 'duc.league');


if ($_GET['bzbbauth'] )
{
	authCheck($ALLOWED_GROUPS);
}

function authCheck ($groups) {
        $args = explode (',', urldecode($_GET['bzbbauth']));
        if ( ! validate_token ($args[0], $args[1], $groups)) {
                echo "Invalid token, or not a member of the appropriate group.<br>";
        } else
		{
			echo '<p>Anmeldung erfolgreich!</p>' . "\n";
			$_SESSION['IsGoodVisitor'] = true;
		}
}


// returns TRUE if user is a member of ANY of the specified groups
// sample reply ... MSG: checktoken callsign=menotume, ip=, token=1279306227  group=duc.league TOKGOOD: menotume:duc.league BZID: 262
function validate_token ($token, $callsign, $groups=array()){
  $list_server='http://my.bzflag.org/db/';

  $group_list='&groups=';
  foreach($groups as $group)
          $group_list.="$group%0D%0A";

  //Trim the last 6 characters, which are "%0D%0A", off of the last group
  $group_list=substr($group_list, 0, strlen($group_list)-6);
	$reply=file_get_contents(''.$list_server.'?action=CHECKTOKENS&checktokens='.urlencode($callsign).'%3D'.$token.''.$group_list.'');
//echo '<pre>';
//print_r($reply);
//echo '</pre>';
  if ( ($x = strpos($reply, "TOKGOOD: $callsign")) !== false) {
    if (count($groups)>0 && $reply{$x + strlen("TOKGOOD: $callsign")}!=':')     // make sure the user is in at least one group                       
      return false;
    return true;
  }
  return false;
}

?>