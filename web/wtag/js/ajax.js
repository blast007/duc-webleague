/*
Copyright &copy; 2008 Pippa http://www.spacegirlpippa.co.uk
Released: 30.05.2008 Stockholm, Sweden
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

/*------ JavaScript functions for the shoutbox -------------------------------*/


// Parse URLs to links
function autoLinks(msg) {

  var text = msg.replace(/((?:ht|f)tps?:\/\/([^\s,]*))/gi,
  "<a href='$1' target='_blank'>{<span class=\"blue\">url</span>}</a>");
  
  return text;

}


// Split too long lines
function splitMsg(msg) {

  var text = msg.replace(/(.[^\s\<\>]{15})/gi,
  "$1\n");

  return text;

}


// Replace smileys tags with images
function replaceSmileys(message) {
  
  var sm = message.replace(/(:\)|:\-\))/g, "<img src='wtag/smileys/smile.gif' width='15' height='15' alt=':)' title=':)' />").
  replace(/(:\(|:-\()/g, "<img src='wtag/smileys/sad.gif' width='15' height='15' alt=':(' title=':(' />").
  replace(/(\;\)|\;\-\))/g, "<img src='wtag/smileys/wink.gif' width='15' height='15' alt=';)' title=';)' />").
  replace(/(:-P)/g, "<img src='wtag/smileys/tongue.gif' width='15' height='15' alt=':-P' title=':-P' />").
  replace(/(S\-\))/g, "<img src='wtag/smileys/rolleyes.gif' width='15' height='15' alt='S-)' title='S-)' />").
  replace(/(\>\()/g, "<img src='wtag/smileys/angry.gif' width='15' height='15' alt='>(' title='>(' />").
  replace(/(\:\*\))/g, "<img src='wtag/smileys/embarassed.gif' width='15' height='15' alt=':*)' title=':*)' />").
  replace(/(\:-D)/g, "<img src='wtag/smileys/grin.gif' width='15' height='15' alt=':-D' title=':-D' />").
  replace(/(QQ)/g, "<img src='wtag/smileys/cry.gif' width='15' height='15' alt='QQ' title='QQ' />").
  replace(/(\=O)/g, "<img src='wtag/smileys/shocked.gif' width='15' height='15' alt='=O' title='=O' />").
  replace(/(\=\/)/g, "<img src='wtag/smileys/undecided.gif' width='15' height='15' alt='=/' title='=/' />").
  replace(/(8\-\))/g, "<img src='wtag/smileys/cool.gif' width='15' height='15' alt='8-)' title='8-)' />").
  replace(/(:-X)/g, "<img src='wtag/smileys/sealedlips.gif' width='15' height='15' alt=':-X' title=':-X' />").
  replace(/(O:\])/g, "<img src='wtag/smileys/angel.gif' width='15' height='15' alt='O:]' title='O:]' />");
  
  return sm;

}


// Add a smiley tag to a message
function tagSmiley(tag) {
  
  var chat_message = document.getElementById('message');
  
  if (chat_message.value == "message")
  {
  chat_message.value = '';
  }
  
  var cache = chat_message.value;
  this.tag = tag;
  chat_message.value = cache + tag;

}


// Clear default value of the name field
// + change the name field text color
function set_focus_n(t) {

  if (t.defaultValue == t.value)
  t.value = '';
  t.style.color = '#000000';

}


// Change the url field text color
function set_focus_u(t) {

  t.style.color = '#000000';

}


// Clear default value of the message field
// + change the message field text color
function set_focus_m(t) {

  if (t.defaultValue == t.value)
  t.value = '';
  t.style.color = 'blue';

}


// Submit on Enter key press
// The function taken from http://www.ryancooper.com/resources/keycode.asp
function checkKeycode(e) {
  
  var keycode;
  
  if (window.event) keycode = window.event.keyCode;
  else if (e) keycode = e.which;
  
  if(keycode == 13)
  {
  sendMessage();
  return false;
  }
  else return true;

}


// Replace bad words with symbols
function filterBW(message) {
  
  for (var i=0; i < badwords.length; i++) {
  var pattern=new RegExp("\\b("+badwords.join("|")+"){1,}\\b",'gi');
  var replacement = "*!*?*";
  text = message.replace(pattern,replacement);
  }
  
  return text;

}


// Validate user input and alert if something goes wrong
function checkInput(message) {
  
  var input_e = "";
  
  input_e += checkMessage(message);
  input_e += checkChars(message);
  input_e += checkSpam(message);
  
  if (input_e != "")
  {
  alert(input_e);
  return false;
  }

  return true;

}


// Check for restricted tags and attributes
function checkChars(msg) {
  
  var error = "";
  
  for (var i=0; i < characters.length; i++) { 
  
  if (msg.indexOf(characters[i])!= -1)
  { 
  error = "Some tags are not allowed. Please use 'http://' if you want to send a url.\n";
  } 
  
  } 
  
  return error;

} 


// Check a message against the banned words list
function checkSpam(msg) {
  
  var error = "";
  
  for (var i=0; i < spamwords.length; i++) {
  
  var pattern = new RegExp("\\b("+spamwords.join("|")+"){1,}\\b",'gi');       
  
  if (pattern.test(msg))
  { 
  error = "Your message contains a banned word.\n";
  }
  } 

  return error;

} 
 

// Validate a name
function checkName(name) {
  
  var error="";
  
  if (name == "" || name == "name")
  {
  error = "You didn't enter a name.\n";
  }
  
  for (var i=0; i < badwords.length; i++) {
  var pattern = new RegExp("\\b("+badwords.join("|")+"){1,}\\b",'gi');
  if ((name.length > 26)||(pattern.test(name)))
  { 
   error = "The name is longer than 26 or contains characters that are not allowed. Please choose another one.\n";
  }
  } 

  return error;

}
 
 
// Validate a message
function checkMessage(message) {
  
  var error="";
  
  if (message=="" || message=="message")
  {
  error = "You didn't enter a message.\n";
  }
  
  if (message.length > 400)
  {
  error = "The message is longer than 400 characters.\n";
  }
  
  return error;

}
 

// Validate a URL
function checkUrl(url) {
  
  var error="";     

  var pattern=new RegExp("((?:ht|f)tps?://.*?)([^\s,]*)\.([^\s,]*)",'gi');
  
  if (!(url=="" || url=="http://"))
  {
  if ((url.length>100)||(!pattern.test(url)))
  { 
  error = "The url you provided seems to be incorrect.\n";
  } 
  }

  return error;

}


/*------ AJAX part -----------------------------------------------------------*/

/*
* The Ajax part of the shoutbox script is based on AJAX-Based Chat System
* by Alejandro Gervasio
* URL: http://images.devshed.com/da/stories/Building_AJAX_Chat/chat_example.zip
*/

// Create the XMLHttpRequestObject
function getXMLHttpRequestObject() {
  var xmlobj;	
  // Check for existing requests
  if (xmlobj!=null&&xmlobj.readyState!=0&&xmlobj.readyState!=4) {
  xmlobj.abort();
  }
  try {
  // Instantiate object for Mozilla, Nestcape, etc.
  xmlobj=new XMLHttpRequest();
  }
  catch(e) {
  try {
  // Instantiate object for Internet Explorer
  xmlobj=new ActiveXObject('Microsoft.XMLHTTP');
  }
  catch(e) {
  // Ajax is not supported by the browser
  xmlobj=null;
  return false;
  }
  }
  return xmlobj;
}



// Check status of sender object
function senderStatusChecker() {
  // Check if request is completed
  if(senderXMLHttpObj.readyState==4) {
  if(senderXMLHttpObj.status==200) {
 
  // If status == 200 display chat data
  displayChatData(senderXMLHttpObj);
  }
  else {
  var post=document.getElementById('content');
  var error_message = document.createTextNode('Failed to get response :'+ senderXMLHttpObj.statusText);
  post.appendChild(error_message);
  }
  }
}


// Check status of receiver object
function receiverStatusChecker() {
  // If request is completed
  if(receiverXMLHttpObj.readyState==4) {
  if(receiverXMLHttpObj.status==200) {
  // If status == 200 display chat data
  displayChatData(receiverXMLHttpObj);
  }
  else {
  var post=document.getElementById('content');
  var error_message = document.createTextNode('Failed to get response :'+ receiverXMLHttpObj.statusText);
  post.appendChild(error_message);
  }
  }
}


// Get messages from database each 5 seconds
function getChatData() {
  receiverXMLHttpObj.open('GET','wtag/getchat.php',true);
  receiverXMLHttpObj.onreadystatechange=receiverStatusChecker;
  receiverXMLHttpObj.send(null);
  $("#message").attr('readonly',false);
  setTimeout('getChatData()',20000);
  
}


// instantiate sender XMLHttpRequest object
var senderXMLHttpObj = getXMLHttpRequestObject();
// instantiate receiver XMLHttpRequest object
var receiverXMLHttpObj = getXMLHttpRequestObject();

 
// Display messages
function displayChatData(reqObj) {
  
  var post=document.getElementById('content');
  
  if(!post)
  {
  return;
  }
  post.innerHTML = '';

  var xmldoc = receiverXMLHttpObj.responseXML;
  if (!xmldoc) return;
  var message_nodes = xmldoc.getElementsByTagName('msg');
  
  for (i = 0; i < message_nodes.length; i++) {
  
  var date = message_nodes[i].getElementsByTagName('date');
  var name = message_nodes[i].getElementsByTagName('name');
  var message = message_nodes[i].getElementsByTagName('message');
 
  var user = document.createElement('div');
  
  user.className = 'user';
  
  var sname = document.createElement('span');
  sname.className = 'name';
  
  var name = document.createTextNode(name[0].firstChild.nodeValue+":");
  sname.appendChild(name);

  slink = sname;
  
  var maintext = document.createElement('span');
  maintext.className='text';
  if (sm_options == 'yes')
  {
  var text = splitMsg(filterBW(autoLinks(replaceSmileys(message[0].firstChild.nodeValue))));
  }
  else
  {
  var text = splitMsg(filterBW(autoLinks(message[0].firstChild.nodeValue)));
  }
  
  maintext.innerHTML += text;
  
  var sdate = document.createElement('span');
  sdate.className='date';
  
  var spl=date[0].firstChild.nodeValue.split(" ");
  var nd = spl[0].substring(0,10);
  var nt = spl[1].substring(0,5);
  var newtime= document.createTextNode(nt);
  
  sdate.title = "Posted on "+nd;
  sdate.appendChild(newtime);
  
  user.appendChild(sdate);
  user.appendChild(slink);
  user.appendChild(maintext);                   
  
  post.appendChild(user);
  
 
}

}


// Send a message
function sendMessage() {
  
  var token = document.getElementById('token').value;
  var message = document.getElementById('message').value;
  
  if (!checkInput( message))
  {
  return;
  }
  
  senderXMLHttpObj.open('POST','wtag/sendchat.php',true);
  senderXMLHttpObj.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
  senderXMLHttpObj.send('token='+token+'&message='+encodeURIComponent(message));
  
  senderXMLHttpObj.onreadystatechange = senderStatusChecker;
  
  var post = document.getElementById('content');
  document.getElementById('message').value = '';
  document.getElementById('message').focus();
  
 
  $("#message").attr('readonly',true);
  
  receiverXMLHttpObj.open('GET','wtag/getchat.php',true);
  receiverXMLHttpObj.onreadystatechange=receiverStatusChecker;
  receiverXMLHttpObj.send(null);
  
  
 
}


// Initialize chat 
function startChat() {
  
  var cform = document.getElementById('cform');
  var msg = document.getElementById('message');
  var submit = document.getElementById('submit');
  var smiley_box = document.getElementById('smiley_box');
  
  
  if (sm_options == 'no') {
  
  smiley_box.style.display = "none";
  
  }
  
  cform.onkeydown = checkKeycode;
  msg.onfocus = function () {set_focus_m(this);}
  submit.onclick = sendMessage;
  
  msg.value = 'message';
  
  getChatData();
  startList();
  
}


window.onload = startChat;
