<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Control Panel{if $title && $title != 'Control Panel'} - {$title|strip_tags}{/if}</title>
	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="none" />
	
	<link href="{get_static_url path='/css/main.css'}" rel="stylesheet" type="text/css" />
	<link href="{get_static_url path='/css/style.css'}" rel="stylesheet" type="text/css" />
	<link href="{get_static_url path='/css/topbar.css'}" rel="stylesheet" type="text/css" />
	<link href="{get_static_url path='/css/ext-all.css'}" type="text/css" rel="stylesheet" />
	<link href="{get_static_url path='/css/ext-scalr-ui.css'}" type="text/css" rel="stylesheet" />
	<link href="{get_static_url path='/css/cp.css'}" type="text/css" rel="stylesheet" />
	
	<script type="text/javascript">
		var load_calendar = {$load_calendar|default:"0"};
		var load_treemenu = {$load_treemenu|default:"0"};
		var get_url = '{$get_url}';
	</script>

    <script type="text/javascript" src="{get_static_url path='/js/prototype-1.6.1.js'}"></script>  
	<script type="text/javascript" src="{get_static_url path='/js/class.Tweaker.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/class.LibWebta.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/common.inc.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/src/scriptaculous.js?load=effects'}"></script>
	
	{if $smarty.get.js_debug}
	<script type="text/javascript" src="{get_static_url path='/js/extjs/ext-prototype-adapter-debug-3.0.3.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/extjs/ext-all-debug-3.0.3.js'}"></script>
	{else}
	<script type="text/javascript" src="{get_static_url path='/js/extjs/ext-prototype-adapter-3.0.3.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/extjs/ext-all-3.0.3.js'}"></script>
	{/if}
	<script type="text/javascript" src="{get_static_url path='/js/extjs/ext-ux.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/scalr-ui.js'}"></script>
	
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
<div id="topbar-wrap">
	<div id="topbar">
		<h1 id="logo"><a title="{t}Home{/t}" href="index.php">Scalr</a></h1>
		<div id="searchbox">
			
			<form action="search.php" id="search_form" method="GET">
				<div style="float:left; vertical-align:middle;">
					<input name="search" class="text_smaller" size="11" id="search_string" value="{$search}" />
				</div>
				<div id="search_button"></div>
			</form>
		</div>
		
		<div id="navmenu"></div>
		
		<div id="toplinks">
			{if $smarty.session.uid != 0}
				<a href="http://groups.google.com/group/scalr-discuss" target="_blank">{t}Support{/t}</a>
			{/if}
			
			<div id="logout_button"></div>
		</div>
		
		<script type="text/javascript">
		// {literal}
		Ext.onReady(function () {
			function calculateNavMenuWidth () {
				return Ext.get("toplinks").getX() - Ext.get("navmenu").getX() - 30;
			}
			
			new Ext.Button({
				renderTo: "search_button", 
				text: "Search", 
				type: "submit",
				handler: function () { Ext.fly("search_form").dom.submit(); }
			});
			new Ext.Button({
				renderTo: "logout_button", 
				text: "Logout", 
				handler: function () { location.href='/login.php?logout=1'; }
			});
		    var navmenuTb = new Ext.Toolbar({
			    renderTo: "navmenu",
			    width: calculateNavMenuWidth(),
			    items: /*{/literal}*/{$menuitems}/*{literal}*/
		    });
		    Ext.EventManager.onWindowResize(function () {
		    	navmenuTb.setWidth(calculateNavMenuWidth());
			});
		});
		// {/literal}
		</script>
				
	</div>
</div>
<div id="body-container">
	<div id="header-title">{$title}</div>
	<div id="header_messages_container" style="margin-bottom:5px;">
		<a name="top"></a>
		{if $warnmsg}
			<div class="header-message warn-message">
				{$warnmsg}
			</div>        
		{/if}
		
		{if $experimental}
			<div class="header-message warn-message">
				{t}This page contains new features that should be considered "experimental".{/t}
			</div>        
		{/if}
				
		{if !$errmsg && $err}
			{assign var="errmsg" value='The following errors have occured:'}
		{/if}
		
		
		<div class="header-message error-message" id="Webta_ErrMsg" style="display:{if $errmsg == ''}none{/if};">
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
			<div class="header-message success-message">{$okmsg}</div>
		{/if}
		
		{if $help}
			<p class="header-message info-message">{$help}</p>
		{/if}
	</div>
	{if !$noheader}
		<form style="margin:0px;padding:0px;" name="frm" id="frm" action="{$form_action}" method="post" {if $upload_files}enctype="multipart/form-data"{/if} {if $onsubmit}onsubmit="{$onsubmit}"{/if}>
	{/if}	
