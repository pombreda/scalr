{include file="inc/header.tpl"}
    <p class="placeholder">Placeholder to show more info about functionality on this page.</p>
    {if $smarty.session.uid != 0}
    {include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" header="Wizards" color="Gray"}
    	<tr>
           <td colspan="2"><a href="app_wizard.php">Set up a server farm and application</a></td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
	<br>
	{/if}
	{include file="inc/table_header.tpl" nofilter=1}

<div id="index_home">
<h3>Server Farms</h3>
<ul>
<li><a href="farms_view.php">View All</a></li>
<li><a href="farms_add.php">Add New</a></li>
</ul>
</div>
<div id="index_home">
<h3>Applications</h3>
<ul>
<li><a href="sites_view.php">View All</a></li>
<li><a href="sites_add.php">Add New</a></li>
</ul>
</div>
<div id="index_home">
<h3>Server Roles</h3>
<ul>
<li><a href="client_roles_view.php">View All</a></li>
<li><a href="client_roles_add.php">Add New</a></li>
</ul>
</div>
<div id="index_home">
<h3>Log</h3>
<ul>
<li><a href="logs_view.php">View Logs</a></li>
</ul>
</div>
<!--
	<table>
	<tr><td><br /><div id="index_menu_div">{$index_menu}</div><br /><br /></td></tr>
	</table>
!-->
	{include file="inc/table_footer.tpl" disable_footer_line=1}

{include file="inc/footer.tpl"}
