{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}		
		{include file="inc/intable_header.tpl" header="System settings" color="Gray"}
		<tr>
			<td colspan="2">Terminate instance if it will not send 'rebootFinish' event after reboot in <input name="reboot_timeout" type="text" class="text" id="reboot_timeout" value="{$reboot_timeout}" size="3"> seconds.</td>
		</tr>
		<tr>
			<td colspan="2">Terminate instance if it will not send 'hostUp' or 'hostInit' event after launch in <input name="launch_timeout" type="text" class="text" id="launch_timeout" value="{$launch_timeout}" size="3"> seconds.</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}
