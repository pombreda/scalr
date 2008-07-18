{include file="inc/header.tpl"}
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Farm role</th>
			<th>Instance ID</th>
			<th>State</th>
			<th>Placement</th>
			<th>Type</th>
			<th>Uptime</th>
			<th>Load averages</th>
			<th>Public IP</th>
			<th>SSH</th>
			<th>Active</th>
			<th>Synchronize</th>
			<th>Logs</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" nowrap><span title="{$rows[id]->instancesSet->item->imageId}">{$rows[id]->Role}</span></td>
		<td class="Item" valign="top" nowrap>{$rows[id]->instancesSet->item->instanceId}</td>
		<td class="Item" valign="top">{$rows[id]->instancesSet->item->instanceState->name}</td>
		<td class="Item" valign="top" nowrap>{$rows[id]->instancesSet->item->placement->availabilityZone}</td>
		<td class="Item" valign="top">{$rows[id]->instancesSet->item->instanceType}</td>
		<td class="Item" valign="top">{$rows[id]->Uptime}</td>
		<td class="Item" valign="top">{$rows[id]->LA}</td>
		<td class="Item" valign="top">{$rows[id]->IP}</td>
		<td class="Item" align="center" valign="middle">{if $rows[id]->IP}<a href="instances_view.php?action=sshClient&farmid={$farmid}&instanceid={$rows[id]->instancesSet->item->instanceId}" target="_blank" ><img style="margin-right:3px;" src="images/terminal.png"></a>{/if}</td>
		<td class="Item" align="center" valign="middle">{if $rows[id]->IsActive}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}</td>
		<td class="Item" valign="top" nowrap>{if $rows[id]->instancesSet->item->instanceState->name == 'running' && $rows[id]->LA != 'Unknown' && $rows[id]->IsRebootLaunched == 0}<a href="syncronize_role.php?iid={$rows[id]->instancesSet->item->instanceId}">Synchronize to all</a>{/if}</td>
        <td class="Item" valign="top"><a href="logs_view.php?iid={$rows[id]->instancesSet->item->instanceId}">View</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" id="actid[]" name="actid[]" value="{$rows[id]->instancesSet->item->instanceId}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="13" align="center">No instances found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="12" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	<input type="hidden" name="farmid" value="{$farmid}" />
	{include file="inc/table_footer.tpl" colspan=9 allow_delete=1 add_new=1}
{include file="inc/footer.tpl"}