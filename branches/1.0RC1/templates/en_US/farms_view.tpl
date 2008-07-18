{include file="inc/header.tpl"}

<p class="placeholder">This is a list of all your Server Farms. A Server Farm is a logical group of EC2 machines that serve your application. It can include load balancers, databases, web severs, and other custom servers. Servers in these farms can be redundant, self curing, and auto-scaling.</p>

    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			{if $smarty.session.uid == 0}<th>Client</th>{/if}
			<th>Farm Name</th>
			<th>Added</th>
			<th>Roles</th>
			<th>Instances</th>
			<th>Applications</th>
			<th>Private key</th>
			<th>Event log</th>
			<th width="1%">Action</th>
			<th width="1%">Edit</th>
			<td width="1%" nowrap>Delete</td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		{if $smarty.session.uid == 0}<td class="Item" valign="top"><a href="clients_view.php?clientid={$rows[id].client.id}">{$rows[id].client.email}</a></td>{/if}
		<td class="Item" valign="top">{$rows[id].name}</td>
		<td class="Item" valign="top">{$rows[id].dtadded}</td>
		<td class="Item" valign="top">{$rows[id].roles} [<a href="roles_view.php?farmid={$rows[id].id}">View</a>]</td>
		<td class="Item" valign="top" nowrap>{$rows[id].instanses} [<a href="instances_view.php?farmid={$rows[id].id}">View</a>]</td>
		<td class="Item" valign="top" nowrap>{$rows[id].sites} [<a href="sites_view.php?farmid={$rows[id].id}">View</a>] {if $smarty.session.uid != 0}[<a href="sites_add.php?farmid={$rows[id].id}">Add</a>]{/if}</td>
		<td class="Item" valign="top"><a href="/storage/keys/{$rows[id].id}/{$rows[id].name}.pem">Download</a></td>
		<td class="Item" valign="top"><a href="logs_view.php?farmid={$rows[id].id}">View</a></td>
		<td class="ItemEdit" valign="top"><a href="farms_control.php?farmid={$rows[id].id}">{if $rows[id].status == 1}Terminate{else}Launch{/if}</a></td>
		<td class="ItemEdit" valign="top"><a href="farms_add.php?id={$rows[id].id}">Edit</a></td>
		<td class="ItemDelete" valign="top"><a href="farm_delete.php?id={$rows[id].id}">Delete</a></td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="{if $smarty.session.uid == 0}11{else}10{/if}" align="center">No farms found!</td>
	</tr>
	{/section}
	<tr>
		<td colspan="{if $smarty.session.uid == 0}8{else}7{/if}" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
{include file="inc/footer.tpl"}