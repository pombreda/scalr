<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Scalr CP{if $logged_as} ({$logged_as}){/if}{if $title && $title != 'Scalr CP'} - {$title|strip_tags}{/if}</title>
	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="none" />

	<link href="{get_static_url path='/css/main.css'}" rel="stylesheet" type="text/css" />
	<link href="{get_static_url path='/css/style.css'}" rel="stylesheet" type="text/css" />
	<link href="{get_static_url path='/css/topbar.css'}" rel="stylesheet" type="text/css" />
	<link href="{get_static_url path='/css/ext-scalr-ui.css'}" type="text/css" rel="stylesheet" />
	<link href="{get_static_url path='/css/cp.css'}" type="text/css" rel="stylesheet" />

	<script type="text/javascript">
		var get_url = '{$get_url}';
	</script>

	{if $smarty.get.js_debug}
		<script type="text/javascript" src="{get_static_url path='/js/extjs-3.2.1/ext-base-debug.js'}"></script>
		<script type="text/javascript" src="{get_static_url path='/js/extjs-3.2.1/ext-all-debug.js'}"></script>
	{else}
		<script type="text/javascript" src="{get_static_url path='/js/extjs-3.2.1/ext-base.js'}"></script>
		<script type="text/javascript" src="{get_static_url path='/js/extjs-3.2.1/ext-all.js'}"></script>
	{/if}

	<link href="/js/extjs-3.2.1/resources/css/ext-all.css" type="text/css" rel="stylesheet" />
	<link href="{get_static_url path='/css/ui-ng/viewers.css'}" type="text/css" rel="stylesheet" />

	<script type="text/javascript" src="{get_static_url path='/js/ext-ux.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/scalr-ui.js'}"></script>
	<script type="text/javascript" src="{get_static_url path='/js/ui-ng/viewers.js'}"></script>

	{$add_to_head}
</head>
<body>
<div id="topbar-wrap">
	<div id="topbar">
		<h1 id="logo"><a title="{t}Home{/t}" href="/login.php">Scalr</a></h1>
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
			{if $smarty.session.Scalr_Session.clientId != 0}
				<a href="http://wiki.scalr.net" target="_blank">{t}Wiki{/t}</a>
				<a href="http://blog.scalr.net" target="_blank">{t}Blog{/t}</a>
				<a href="http://groups.google.com/group/scalr-discuss" target="_blank">{t}Support{/t}</a>
				<a href="http://twitter.com/scalr" target="_blank">{t}Follow us on Twitter{/t}</a>

			{/if}
		</div>
		<div id="logout_button"></div>

		<script type="text/javascript">
		var session_environments = eval({$session_environments});

		// {literal}

		Ext.onReady(function () {
			Ext.BLANK_IMAGE_URL = Ext.isIE6 || Ext.isIE7 || Ext.isAir ?
                            'https:/' + '/www.sencha.com/s.gif' :
                            'data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

                        Ext.QuickTips.init();

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
				handler: function () {
					// clear state
					Ext.state.Manager.getProvider().clearAll();
					location.href='/login.php?logout=1';
				}
			});
		    var navmenuTb = new Ext.Toolbar({
			    renderTo: "navmenu",
			    width: calculateNavMenuWidth(),
			    items: /*{/literal}*/{$menuitems}/*{literal}*/
		    });

			Ext.state.Manager.setProvider(new Scalr.state.StorageProvider());

		    Ext.EventManager.onWindowResize(function () {
		    	navmenuTb.setWidth(calculateNavMenuWidth());
			});

		    if (session_environments)
		    {
				new Ext.Button({
					template: new Ext.Template(
						'<div id="top-environment-item"><img src="/images/ui-ng/icons/environment_16x16.png" style="float: left"><div style="float: left; padding-left: 6px;"></div></div>'
					),
					buttonSelector: 'div',
					renderTo: 'top-environment',
					text: session_environments.current,
					menu: new Ext.menu.Menu({
						items: session_environments.list,
						listeners: {
							'itemclick': function (item, e) {
								if (item.checked == false) {
									document.location.href = '/client_dashboard.php?' + Ext.urlEncode({
										'change_environment_id': item.envId,
										'change_environment_redirect': document.location.href
									});
								}
							}
						}
					})
				});
		    }

			{/literal}
			var errmsg = '';

			{if !$errmsg && $err}
				{assign var="errmsg" value='The following errors have occured:'}
			{/if}

			{if $errmsg != ''}
				errmsg = '{$errmsg|replace:"'":"\'"}';

				{if $err}
					errmsg += '<span style="color: #CB3216">';
						{foreach from=$err key=id item=field}
							errmsg += '<br />&bull;&nbsp;&nbsp;{$field|replace:"'":"\'"}';
						{/foreach}
					errmsg += '</span>';
				{/if}
				Scalr.Viewers.ErrorMessage(errmsg);
			{/if}

			{if $okmsg}
				Scalr.Viewers.SuccessMessage('{$okmsg}');
			{/if}

			{if $help}
				Ext.get('top-title-info').applyStyles('display: inline');
				new Ext.ToolTip({ldelim}
					target: 'top-title-info',
					html: '{$help|replace:"'":"\'"}',
					dismissDelay: 0
				{rdelim});
			{/if}

			{literal}
		});
		{/literal}
		</script>
	</div>
	<div id="topfoot">
		<div id="top-title">{$title} <img src="/images/ui-ng/icons/info_icon_14x14.png" id="top-title-info"></div>
		<div id="top-environment"></div>
		<div id="top-messages"></div>
		<div id="top-messages-icons"><img src="/images/ui-ng/icons/message/close.png" class="close" alt="Close all messages"><img src="/images/ui-ng/icons/message/eye.png" class="eye"></div>
	</div>
</div>

<div id="body-container">
	<div id="header_messages_container" style="margin-bottom:5px;">
		<a name="top"></a>
		{if $warnmsg}
			<div class="header-message warn-message">
				{$warnmsg}
			</div>
		{/if}

		{if $experimental}
			<div class="header-message warn-message">
				{t}This page contains new features that should be considered "experimental". <a href="http://support.scalr.net">Drop us a line</a> if you notice any issues.{/t}
			</div>
		{/if}
		{$aws_problems}

		<div class="header-message success-message" id="Webta_OkMsg" style="display: none"></div>
	</div>
	{if !$noheader}
		<form style="margin:0px;padding:0px;" name="frm" id="frm" action="{$form_action}" method="post" {if $upload_files}enctype="multipart/form-data"{/if} {if $onsubmit}onsubmit="{$onsubmit}"{/if}>
	{/if}
