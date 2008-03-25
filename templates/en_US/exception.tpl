{include file="inc/login_header.tpl"}	

<div align="center">
    <br>
	<h1>Application threw an exception</h1>

	<div id="details">

		<p>Sorry, we have some troubles on our server. Please visit us a bit later</p>
		<p><div style="color: red;">{$message}</div></p>
	
	</div>	<!-- /details -->
</div>

<div style="font-size:11px;">{$backtrace}</div>
	
{include file="inc/login_footer.tpl"}
