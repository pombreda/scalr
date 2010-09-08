<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Control Panel{if $title && $title != 'Control Panel'} - {$title|strip_tags}{/if}</title>
	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="none" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	
	<script type="text/javascript">
		var load_calendar = {$load_calendar|default:"0"};
		var load_treemenu = {$load_treemenu|default:"0"};
		var get_url = '{$get_url}';
	</script>
	<script type="text/javascript" src="/js/prototype-1.6.0.3.js"></script>
	<script type="text/javascript" src="/js/class.Tweaker.js"></script>
	<script type="text/javascript" src="/js/class.LibWebta.js"></script>
	<script type="text/javascript" src="/js/common.inc.js"></script>
	<script type="text/javascript" src="/js/src/scriptaculous.js?effects"></script>

	<link href="css/main.css" rel="stylesheet" type="text/css" />
	<link href="css/style.css" rel="stylesheet" type="text/css" />
	
	{if $load_extjs}
	<script type="text/javascript" src="/js/extjs/ext-prototype-adapter.js"></script>
	<script type="text/javascript" src="/js/extjs/ext-all-debug.js"></script>
	<script type="text/javascript" src="/js/extjs/ext-ux.js"></script>
	<script type="text/javascript" src="/js/scalr-ui.js"></script>
		{if !$no_extjs_style}
			<link type="text/css" rel="stylesheet" href="/css/ext-all.css" />
		{/if}
	<link type="text/css" rel="stylesheet" href="/css/ext-scalr-ui.css" />
	{/if}
	
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
	{$add_to_head}
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
			<td width="90">
				<img src="images/logo_header.png" />
			</td>
			<td width="200" nowrap valign="middle">
				<form action="index.php" method="post" name="serach_form" onsubmit="webtacp.search(); return false;">
				<div style="float:left; vertical-align:middle;"><input name="search" id="search_string" type="text" class="text_smaller" size="11" /><img id="search_image" style="margin-left: -18px; vertical-align:middle; display:none;" src="images/loading.gif"></div>
				<input id="search_button" type="submit"  value="Search" class="btn" style="margin-top:2px; margin-left: 3px;" />
				</form>
			</td>
			<td style="overflow: hidden;">
				{$dmenu}
			</td>
			<td align="right">
				<a href="http://groups.google.com/group/scalr-discuss" style='text-decoration:underline;color:white;font-family:Tahoma;font-size:9pt;' target="_blank">Support</a>
				&nbsp;&nbsp;
				<input type="button" value="Logout" onClick="document.location='login.php?logout=1'" class="btn" style="vertical-align:middle;" />
			</td>
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
        	<div id="header_messages_container">
        	{if $experimental}
				<div class="Webta_ExperimentalMsg" style="margin-top:10px;margin-bottom:10px;">
					This page contains new features that should be considered "experimental". Drop us a line if you notice any issues.
				</div>        
			{/if}
			
			{if $warnmsg}
				<div class="Webta_ExperimentalMsg" style="margin-top:10px;margin-bottom:10px;">
					{$warnmsg}
				</div>        
			{/if}
			
			{if $mess != ''}
				<div class="Webta_Message">{$mess}</div>
			{/if}
			
			{if !$errmsg && $err}
				{assign var="errmsg" value='The following errors have occured:'}
			{/if}
			
			<div class="Webta_ErrMsg" id="Webta_ErrMsg" style="display:{if $errmsg != ''}{else}none{/if};">
			{if $errmsg != ''}
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
				
				{literal}
				<script language="Javascript" type="text/javascript">
					Event.observe(window, 'load', function(){new Effect.Pulsate($('Webta_ErrMsg'));}); 
				</script>
				{/literal}
			{/if}
			</div>
			
			{if $okmsg != ''}
				<div class="Webta_OkMsg">{$okmsg}</div>
			{/if}
			{if $err && !$errmsg}
				<table width="100%" cellpadding="10" cellspacing="1" bgcolor="#E5E5E5">
					<tr>
						<td bgcolor="#FFFFFF">
							<span style="color: #FF0000">
							{t}The following errors have occured:{/t}<br>
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
							{t}Warnings: {/t}<br>
							<br>
							{foreach from=$warn key=id item=field}
								&bull;&nbsp;&nbsp;{$field}<br>
							{/foreach}
							</span>
						</td>
					</tr>
      			</table>
			{/if}
			<a name="top"></a>
			{if $help}
				<p class="placeholder">{$help}</p>
			{/if}
		</div>
		{if !$noheader}
			<form name="frm" id="frm" action="{$form_action}" method="post" {if $upload_files}enctype="multipart/form-data"{/if} {if $onsubmit}onsubmit="{$onsubmit}"{/if}>
		{/if}		