<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title>Control Panel{if $title && $title != 'Control Panel'} - {$title|strip_tags}{/if}</title>
	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="none" />

	<link href="css/main.css" rel="stylesheet" type="text/css" />
	<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="inc/newstyle.css" rel="stylesheet" type="text/css" />	

	<script type="text/javascript">
		var load_calendar = {$load_calendar|default:"0"};
		var load_treemenu = {$load_treemenu|default:"0"};
	</script>
	<script type="text/javascript" src="js/prototype.inc.js"></script>
	<script type="text/javascript" src="js/class.Tweaker.js"></script>
	<script type="text/javascript" src="js/class.LibWebta.js"></script>
	<script type="text/javascript" src="js/common.inc.js"></script>
	
	<script language="Javascript" type="text/javascript">
	{literal}
	 var allchecked = false;
     function checkall()
     {
     	var frm = $("frm");
    	for (var i=0;i<frm.elements.length;i++)
    	{
    		var e = frm.elements[i]
    		if ((e.name == "delete[]") && (e.type=='checkbox') && !e.disabled) {
    			e.checked = !allchecked;
    		}
    	}
    	allchecked = !allchecked;
     }
 {/literal}
	</script>

<style type="text/css">
{literal}
body {
background: url("http://designcanada.dreamhosters.com/scalr/images/background.gif") repeat-x top #fff;
}
table.Header {
background: none;
}
div.TopMenu table {
}
div.TopMenu {
background: none;
height: 35px;
border: 1px solid #b93434;
}
div.TopMenu table tr td.item {
font-size: 16px;
padding: 8px;
height: 30px;
color: #fff;
}
div.TopMenu table tr td.item:hover {
background: url("http://designcanada.dreamhosters.com/scalr/images/nav-back.gif") repeat-x top;
border: none;
}
table.Header #TopMenu.TopMenu li ul li {
width: 200px;
padding: 4px;
background: #000;
}
div.TopMenu table tr td.item.has-submenu.active {
border: none;
background: url("http://designcanada.dreamhosters.com/scalr/images/nav-back.gif") repeat-x top;
}
div.TableHeaderCenter {
background: none;
}
td.TableHeaderCenter {
background: none;
}
div.TableHeaderLeft {
background: none;
}
div.TableHeaderRight {
background: none;
}
div.TableFooterCenter {
background: none;
}
td.TableFooterCenter {
background: none;
}
div.TableFooterLeft {
background: none;
}
div.TableFooterRight {
background: none;
}
td#title_td {
padding: 5px;
font-size: 18px;
font-weight: bold;
color: #333;
}
form#frm p {
padding: 5px;
padding-top: 0;
}
table#Webta_Settings {
}
table#Webta_Settings.changed {
background: #000;
}
ul#IndexMenu {
list-style: none;
}
ul#IndexMenu li {
padding-left: 15px;
background: url("http://designcanada.dreamhosters.com/scalr/images/bullet.gif") no-repeat left top;
}
ul#IndexMenu li ul li {
list-style: none;
padding-left: 15px;
background: url("http://designcanada.dreamhosters.com/scalr/images/ul-arrow.gif") no-repeat left top;
}
ul#IndexMenu li ul li ul li {
list-style: none;
padding-left: 15px;
background: url("http://designcanada.dreamhosters.com/scalr/images/blue-bullet.gif") no-repeat left top;
}
#footer_footer {
width: 300px;
margin: 10px auto;
text-align: center;
font-size: 12px;
}
#footer_footer a img {
margin-bottom: 10px;
}
#TopMenu li ul li a {
background: #a21a1a;
color: #fff;
}
#Webta_Settings tr {
border: 1px solid #a21a1a;
}
#Webta_Settings thead tr {
border-bottom: 1px solid #d8d6d6;
background: url("http://designcanada.dreamhosters.com/scalr/images/thead.gif") repeat-x top;
}
#Webta_Settings thead th {
text-transform: uppercase;
color: #860b0b;
padding-top: 4px;
font-size: 14px;
letter-spacing: -1px;
}
#Webta_Settings tr {
border-bottom: 1px solid #fff;
font-weight: bold;
color: #333;
}
#Webta_Settings tr#tr_1 a {
color: #3f74a0;
}
table.WebtaTable_Footer {
background: url("http://designcanada.dreamhosters.com/scalr/images/thead.gif") repeat-x top #fff;
}
td.TableHeaderCenter {
background: none;
}
td.TableHeaderCenter {
background: none;
}
div#index_home {
width: 206px;
height: 151px;
margin: 10px;
float: left;
font-weight: bold;
background: url("http://designcanada.dreamhosters.com/scalr/images/home-box.gif") no-repeat top;
}
div#index_home h3 {
color: #a21a1a;
text-transform: uppercase;
padding: 5px 10px;
font-family: Arial, sans-serif;
}
div#index_home ul {
margin-left: -34px;
margin-top: -10px;
}
div#index_home ul li {
list-style: none;
padding-left: 15px;
background: url("http://designcanada.dreamhosters.com/scalr/images/ul-arrow.gif") no-repeat left top;
}
.TableHeaderLeft_Gray {
background: none;
}
.TableHeaderRight_Gray {
background: none;
}

input#filter_q {
margin-left: -20px;
}
.WebtaTable_Footer {
border: none;
}
#frm p.placeholder {
border: 1px solid #888;
background: #eee;
font-weight: bold;
padding: 5px;
color: #444;
}
{/literal}
</style>

</head>

<body onload="webtacp.afterload()" onresize="webtacp.setupTweaker()">


<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table" width="100%">
<tr>
	<td width="7"><div class="TableHeaderLeft"></div></td>
	<td><div class="TableHeaderCenter"></div></td>
	<td width="7"><div class="TableHeaderRight"></div></td>
</tr>
<tr>
	<td width="7" class="TableHeaderCenter"></td>
	<td><table border="0" width="100%" cellpadding="0" cellspacing="0" class="Header">
		<tr>
			<td width="200" nowrap valign="middle">
<img src="http://designcanada.dreamhosters.com/scalr/images/logo.gif" alt="Scalr" style="margin-top: -10px;" />
				<!-- <form action="index.php" method="post" name="serach_form" onsubmit="webtacp.search(); return false;">
				<div style="float:left; vertical-align:middle;"><input name="search" id="search_string" type="text" class="text_smaller" size="11" /><img id="search_image" style="margin-left: -18px; vertical-align:middle; display:none;" src="images/loading.gif"></div>
				<input id="search_button" type="submit"  value="Search" class="btn" style="margin-top:2px; margin-left: 3px;" />
				</form> -->
			</td>
			<td style="overflow: hidden;">
				{$dmenu}
			</td>
			<td align="right"><input type="button" value="Logout" onClick="document.location='login.php?logout=1'" class="btn" /></td>
		</tr>
		</table></td>
	<td width="7" class="TableHeaderCenter"></td>
</tr>
<tr>
	<td width="7"><div class="TableFooterLeft"></div></td>
	<td><div class="TableFooterCenter"></div></td>
	<td width="7"><div class="TableFooterRight"></div></td>
</tr>
</table>
	

<br>
<table width="100%" height="100%" cellpadding="5" cellspacing="0">

  <tr>
	<td width="76%" valign="top"><table width="100%" cellspacing="0" cellpadding="0">
	  <tr>
		<td height="17" class="mg" id="title_td">{$title}</td>
	  </tr>
	  <tr>
        <td>
			{if $mess != ''}
				<div class="Webta_Message">{$mess}</div>
			{elseif $errmsg != ''}
				<div class="Webta_ErrMsg">
				    {$errmsg}
				    {if $err}
				    <table style="margin-top:0px;" width="100%" cellpadding="5" cellspacing="1" bgcolor="">
    					<tr>
    						<td bgcolor="">
    							<span style="color: #CB3216">
    							{foreach from=$err key=id item=field}
    								&bull;&nbsp;&nbsp;{$field}<br>
    							{/foreach}
    							</span>
    						</td>
    					</tr>
          			</table>
          			{/if}
				</div>
			{elseif $okmsg != ''}
				<div class="Webta_OkMsg">{$okmsg}</div>
			{/if}
			{if $err && !$errmsg}
				<table width="100%" cellpadding="10" cellspacing="1" bgcolor="#E5E5E5">
					<tr>
						<td bgcolor="#FFFFFF">
							<span style="color: #FF0000">
							The following errors have occured: <br>
							<br>
							{foreach from=$err key=id item=field}
								&bull;&nbsp;&nbsp;{$field}<br>
							{/foreach}
							</span>
						</td>
					</tr>
      			</table>
			{/if}
        	{if $warn}
				<table width="100%" cellpadding="10" cellspacing="1" bgcolor="#E5E5E5">
					<tr>
						<td bgcolor="#FFFFFF">
							<span style="color: #FF0000">
							Warnings: <br>
							<br>
							{foreach from=$warn key=id item=field}
								&bull;&nbsp;&nbsp;{$field}<br>
							{/foreach}
							</span>
						</td>
					</tr>
      			</table>
			{/if}        
			{if !$noheader}
				<form name="frm" id="frm" action="{$form_action}" method="post" {if $upload_files}enctype="multipart/form-data"{/if} {if $onsubmit}onsubmit="{$onsubmit}"{/if}>
			{/if}
		<a name="top"></a>
