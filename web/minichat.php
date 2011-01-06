<?php
session_start();
$token = md5(uniqid(rand(), true));
$_SESSION['token'] = $token;
?>
<!-- 1. Important: the PHP code above must appear at the top of any page which includes the shoutbox (before any HTML is started). -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<title>wTag test page.</title>

<!-- Copy the code between "Start Meta and links" and "End Meta and links",
paste it before the </head> closing tag on every page which includes the shoutbox
-->

<!-- 2. Start Meta and links -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">
/* for Mozilla only: create rounded corners */
#box {
-moz-border-radius: 10px 10px 10px 10px;
}
</style>
<link rel="stylesheet" type="text/css" href="wtag/css/main.css" />
<link rel="stylesheet" type="text/css" href="wtag/css/main-style.css" />
<!--[if IE]>
<link rel="stylesheet" type="text/css" href="wtag/css/ie-style.css" />
<![endif]-->
<script type="text/javascript" src="wtag/js/dom-drag.js"></script>
<script type="text/javascript" src="wtag/js/scroll.js"></script>
<script type="text/javascript" src="wtag/js/conf.js"></script>
<script type="text/javascript" src="wtag/js/ajax.js"></script>
<script type="text/javascript" src="wtag/js/drop_down.js"></script>
<!-- 2. End Meta and links -->

</head>

<body>
<!--
Copyright &copy; 2008 Pippa http://www.spacegirlpippa.co.uk
Contact: sthlm.pippa@gmail.com
Released: 30.05.2008 Stockholm, Sweden

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
-->

<div id="main">

<div id="corner_tl">
</div>
<div id="corner_tr">
</div>
<div id="corner_bl">
</div>
<div id="corner_br">
</div>

<!-- 3. Shoutbox code start -->
<div id="box">

<div id="chat">

<div id="scrollArea">
<div id="scroller">
</div>
</div>

<div id="container">
<div id='content'>
</div>
</div>

<div id='form'>
<form id='cform' name='chatform' action="#" >
<div id='field_set'>
<input type='hidden' id='token' name='token' value='<?php echo $token; ?>' />
<input type='text' id='name' name='name' value='name' />
<input type='text' id='url' name='url' value='http://' />
<textarea rows='4' cols='10' id='message'  name='message' >message</textarea>
</div>
</form>
</div> 

<div id="chat_menu">

<div id ="wtag">
<p>
<!-- You may not remove the copyright link, but you may edit its look and location on your web page -->
<a href="http://spacegirlpippa.co.uk" title="A free mini chat (shoutbox) script">
wTag
</a>
</p>
</div>

<div id='refresh'>
<p>refresh</p>
</div>

<div id='emo'>
<ul id="show_sm">
<li>
smileys
<ul id="smiley_box">
<li>
<img class='smileys' src='wtag/smileys/smile.gif' width='15' height='15' alt=':)' title=':)' onclick = "tagSmiley(':)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/sad.gif' width='15' height='15' alt=':(' title=':(' onclick = "tagSmiley(':(');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/wink.gif' width='15' height='15' alt=';)' title=';)' onclick = "tagSmiley(';)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/tongue.gif' width='15' height='15' alt=':-P' title=':-P' onclick = "tagSmiley(':-P');"/>
</li>
<li>
<img class='smileys' src='wtag/smileys/rolleyes.gif' width='15' height='15' alt='S-)' title='S-)' onclick = "tagSmiley('S-)');"/>
</li>
<li>
<img class='smileys' src='wtag/smileys/angry.gif' width='15' height='15' alt='>(' title='>(' onclick = "tagSmiley('>(');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/embarassed.gif' width='15' height='15' alt=':*)' title=':*)' onclick = "tagSmiley(':*)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/grin.gif' width='15' height='15' alt=':-D' title=':-D' onclick = "tagSmiley(':-D');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/cry.gif' width='15' height='15' alt='QQ' title='QQ' onclick = "tagSmiley('QQ');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/shocked.gif' width='15' height='15' alt='=O' title='=O' onclick = "tagSmiley('=O');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/undecided.gif' width='15' height='15' alt='=/' title='=/' onclick = "tagSmiley('=/');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/cool.gif' width='15' height='15' alt='8-)' title='8-)' onclick = "tagSmiley('8-)');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/sealedlips.gif' width='15' height='15' alt=':-X' title=':-X' onclick = "tagSmiley(':-X');" />
</li>
<li>
<img class='smileys' src='wtag/smileys/angel.gif' width='15' height='15' alt='O:]' title='O:]' onclick = "tagSmiley('O:]');" />
</li>
</ul>
</li>

</ul>
</div>

<div id='submit'>
<p>submit</p>
</div>

</div>

</div>
</div>
<!-- 3. Shoutbox code end -->

<div id ="links">
<a href="http://spacegirlpippa.co.uk/minichat/shoutbox-installation.php"><span class="accent">wTag installation guide.</span></a>
</div>

</div>


</body>
</html>