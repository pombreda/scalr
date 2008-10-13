<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>SSH console on instance: {$i.instance_id} ({$i.role_name}) &nbsp; IP: {$i.external_ip} &nbsp; Internal IP: {$i.internal_ip}</title>
</head>
<body>
	<div style="margin-top:22px;margin-bottom:38px;font-weight:bold;font-size:10pt;font-family:Verdana;">SSH console on instance: {$i.instance_id} ({$i.role_name}) &nbsp; IP: {$i.external_ip} &nbsp; Internal IP: {$i.internal_ip}</div>

	<APPLET CODE="com.mindbright.application.MindTerm.class" ARCHIVE="/java/mindterm.jar?r1" WIDTH=800 HEIGHT=600>
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
		<PARAM NAME="username" value="root">
		<PARAM NAME="auth-method" value="publickey">
		<PARAM NAME="fg-color" value="white">
		<PARAM NAME="bg-color" value="black">
		<PARAM NAME="private-key-str" value="{$key}">
		<PARAM NAME="geometry" value="105x35">
	</APPLET>
</body>
</html>