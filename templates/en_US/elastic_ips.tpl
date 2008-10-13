{include file="inc/header.tpl"}
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Farm name</th>
			<th>Role name</th>
			<th>IP address</th>
			<th>Used by role</th>
			<th>Instance</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">
		{if $rows[id]->dbInfo || $rows[id]->dbInstance}
			<a href="farms_view.php?id={$rows[id]->farmId}">{$rows[id]->farmName}</a>
		{else}
			Not used by Scalr
		{/if}
		</td>
		<td class="Item" valign="top">
		{if $rows[id]->dbInfo}
			<a href="roles_view.php?farmid={$rows[id]->farmId}">{$rows[id]->dbInfo.role_name}</a>
		{elseif $rows[id]->dbInstance}
			<a href="roles_view.php?farmid={$rows[id]->farmId}">{$rows[id]->dbInstance.role_name}</a>
		{else}
			Not used by Scalr
		{/if}
		</td>
		<td class="Item" valign="top">{$rows[id]->publicIp}</td>
		<td class="Item" valign="top">
			{if $rows[id]->dbInfo}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}
		</td>
		<td class="Item" valign="top">
		{if $rows[id]->dbInfo || $rows[id]->dbInstance}
			<a href="instances_view.php?iid={$rows[id]->instanceId}&farmid={$rows[id]->farmId}">{$rows[id]->instanceId}</a>
		{else}
			{$rows[id]->instanceId}
		{/if}
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="5" align="center">No elastic IPs allocated</td>
	</tr>
	{/section}
	<tr>
		<td colspan="5" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
{include file="inc/footer.tpl"}