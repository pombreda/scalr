{include file="inc/header.tpl"}
    {if $smarty.session.uid != 0}
    {include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" header="Wizards" color="Gray"}
    	<tr>
    	   <td colspan="2"><img src="/images/wizard.png" style="vertical-align:middle;">&nbsp;<a href="app_wizard.php">Create new application</a></td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
	<br>
	{/if}
	<br>
	{include file="inc/table_header.tpl" nofilter=1}
	<table>
	<tr><td><br /><div id="index_menu_div">{$index_menu}</div><br /><br /></td></tr>
	</table>
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}
