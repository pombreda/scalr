{include file="inc/header.tpl"}
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Farm role</th>
			<th>Instance ID</th>
			<th>State</th>
			<th>Uptime</th>
			<th>Load averages</th>
			<th>Public IP</th>
			<th>Syncronize</th>
			<th>Logs</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" nowrap>{$rows[id]->Role}</td>
		<td class="Item" valign="top">{$rows[id]->instancesSet->item->instanceId}</td>
		<td class="Item" valign="top">{$rows[id]->instancesSet->item->instanceState->name}</td>
		<td class="Item" valign="top">{$rows[id]->Uptime}</td>
		<td class="Item" valign="top">{$rows[id]->LA}</td>
		<td class="Item" valign="top">{$rows[id]->IP}</td>
		<td class="Item" valign="top"><a href="syncronize_role.php?iid={$rows[id]->instancesSet->item->instanceId}">Syncronize to all</a></td>
        <td class="Item" valign="top"><a href="logs_view.php?iid={$rows[id]->instancesSet->item->instanceId}">View</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" id="actid[]" name="actid[]" value="{$rows[id]->instancesSet->item->instanceId}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="9" align="center">No instances found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="8" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	<input type="hidden" name="farmid" value="{$farmid}" />
	{include file="inc/table_footer.tpl" colspan=9 allow_delete=1 add_new=1}	
{include file="inc/footer.tpl"}