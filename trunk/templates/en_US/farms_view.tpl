{include file="inc/header.tpl"}

<p class="placeholder">This is a list of all your Server Farms. A Server Farm is a logical group of EC2 machines that serve your application. It can include load balancers, databases, web severs, and other custom servers. Servers in these farms can be redundant, self curing, and auto-scaling.</p>

    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Farm Name</th>
			<th>Added</th>
			<th>Roles</th>
			<th>Instances</th>
			<th>Applications</th>
			<th>Private key</th>
			<th width="1%">Action</th>
			<th width="1%">Edit</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].name}</td>
		<td class="Item" valign="top">{$rows[id].dtadded}</td>
		<td class="Item" valign="top">{$rows[id].roles} [<a href="roles_view.php?farmid={$rows[id].id}">View</a>]</td>
		<td class="Item" valign="top" nowrap>{$rows[id].instanses} [<a href="instances_view.php?farmid={$rows[id].id}">View</a>]</td>
		<td class="Item" valign="top" nowrap>{$rows[id].sites} [<a href="sites_view.php?farmid={$rows[id].id}">View</a>] {if $smarty.session.uid != 0}[<a href="sites_add.php?farmid={$rows[id].id}">Add</a>]{/if}</td>
		<td class="Item" valign="top"><a href="farms_view.php?id={$rows[id].id}&task=download_private_key">Download</a></td>
		<td class="ItemEdit" valign="top"><a href="farms_control.php?farmid={$rows[id].id}">{if $rows[id].status == 1}Terminate{else}Launch{/if}</a></td>
		<td class="ItemEdit" valign="top"><a href="farms_add.php?id={$rows[id].id}">Edit</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="10" align="center">No farms found!</td>
	</tr>
	{/section}
	<tr>
		<td colspan="6" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 on_delete_alert_message="This will terminate all instances in this farm! Are you sure?"}
{include file="inc/footer.tpl"}