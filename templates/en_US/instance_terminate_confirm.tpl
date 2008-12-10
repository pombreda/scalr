{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Confirmation" color="Gray"}
			<tr>
				<td colspan="2">
					You are about to terminate instance <b>{$instance_id}</b>. Your current Miminum instances setting for role {$role_name} is {$min_count}. 
					If you do not decrease the Miminum instances setting, Scalr will launch a new instance to replace the terminated one.
				</td>
			</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{if $action}
			<input type="hidden" name="action" value="{$action}" />
			<input type="hidden" name="delete[]" value="{$instance_id}" />
		{/if}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Terminate instance and decrease Mininimum instances to $min_count_new" button3=1 button3_name="Terminate instance and do not decrease setting" cancel_btn=1}
{include file="inc/footer.tpl"}