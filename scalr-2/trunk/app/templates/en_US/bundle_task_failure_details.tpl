{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="Details" color="Gray"}
        <tr>
			<td width="20%" style="padding:5px;">Task ID:</td>
			<td style="padding:5px;">{$task.id}</td>
		</tr>
		<tr>
			<td width="20%" style="padding:5px;">Server ID:</td>
			<td style="padding:5px;">{$task.server_id}</td>
		</tr>
		<tr>
			<td width="20%" style="padding:5px;">Role Name:</td>
			<td style="padding:5px;">{$task.rolename}</td>
		</tr>
		<tr>
			<td width="20%" style="padding:5px;">Type:</td>
			<td style="padding:5px;">{$task.platform}/{$task.bundle_type}</td>
		</tr>
		<tr>
			<td width="20%" style="padding:5px;">Failure reason:</td>
			<td style="color:red;padding:5px;">{$task.failure_reason}</td>
		</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}