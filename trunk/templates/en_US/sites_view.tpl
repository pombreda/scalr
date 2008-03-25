{include file="inc/header.tpl"}
    <p class="placeholder">Placeholder to show more info about functionality on this page.</p>
	{include file="inc/table_header.tpl"}
	<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
	<thead>
	<tr>
		<th>Application</th>
		<th>Farm</th>
		<th>Role</th>
		<th nowrap width="120">Edit</th>
		<th nowrap width="1%"><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].zone}</td>
		<td class="Item" valign="top"><a href="farms_view.php?farmid={$rows[id].farm.id}">{$rows[id].farm.name}</a></td>
		<td class="Item" valign="top"><a href="roles_view.php?farmid={$rows[id].farm.id}&ami_id={$rows[id].role.ami_id}">{$rows[id].role.name}</a></td>
		<td class="ItemEdit" valign="top" nowrap>{if $rows[id].isdeleted == 0}<a href="sites_add.php?ezone={$rows[id].zone}">Edit DNS Zone</a>{/if}</td>
		<td class="ItemDelete">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="7" align="center">No applications found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="3" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}
{include file="inc/footer.tpl"}