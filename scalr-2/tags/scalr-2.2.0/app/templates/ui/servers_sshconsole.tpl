<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>{t}SSH console on server{/t}: {$DBServer->serverId} {t}Remote IP{/t}: {$DBServer->remoteIp} &nbsp; {t}Internal IP{/t}: {$DBServer->localIp}</title>
<script type="text/javascript">
	var error = '{$error}';
	{literal}
	if (error) {
		if (window.opener) {
			// parent window
			window.opener.Scalr.Viewers.ErrorMessage(error);
			window.close();
		} else {
			// root window
			document.location.href = '/#/servers/view';
		}
	}
	{/literal}
</script>
</head>
<body style="overflow:hidden; background-color:#DFE8F6;">
	<div style="margin-top:22px;font-weight:bold;font-size:10pt;font-family:Verdana;">{t}SSH console on server{/t}: {$DBServer->serverId} &nbsp; {t}IP{/t}: {$DBServer->remoteIp} &nbsp; {t}Internal IP{/t}: {$DBServer->localIp}</div>
	<div style="margin-top:5px;font-weight:bold;font-size:10pt;font-family:Verdana;">Farm: {$DBFarm->Name} (ID: {$DBServer->farmId})</div>
	<div style="margin-top:5px;margin-bottom:38px;font-weight:bold;font-size:10pt;font-family:Verdana;">Role: {$DBRole->name}</div>
	<APPLET CODE="com.mindbright.application.MindTerm.class" ARCHIVE="/java/mindterm.jar?r1" WIDTH=900 HEIGHT=600>
		<PARAM NAME="sepframe" value="false">  
		<PARAM NAME="debug" value="false">
		<PARAM NAME="quiet" value="true">
		<PARAM NAME="menus" value="no">
		<PARAM NAME="exit-on-logout" value="true">
		<PARAM NAME="allow-new-server" value="false">
		<PARAM NAME="savepasswords" value="false">
		<PARAM NAME="verbose" value="false">
		<PARAM NAME="useAWT" value="false">
		<PARAM NAME="protocol" value="ssh2">
		<PARAM NAME="server" value="{$host}">
		<PARAM NAME="port" value="{$port}">
		<PARAM NAME="username" value="root">
		<PARAM NAME="auth-method" value="publickey">
		<PARAM NAME="fg-color" value="white">
		<PARAM NAME="bg-color" value="black">
		<PARAM NAME="private-key-str" value="{$key}">
		<PARAM NAME="geometry" value="125x35">
	</APPLET>
</body>
</html>