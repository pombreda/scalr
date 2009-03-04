{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl" show_region_filter=1}
	<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
	<thead>
	<tr>
		<th>Name</th>
		<th>Description</th>
		<th nowrap width="1%">Edit</th>
		<th width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></th>
	</tr>
	</thead>
	<tbody>
    	{section name=id loop=$rows}
    	<tr id='tr_{$smarty.section.id.iteration}'>
    		<td class="Item" valign="top">{$rows[id]->groupName}</td>
    		<td class="Item" valign="top">{$rows[id]->groupDescription}</td>
    		<td class="ItemEdit" valign="top"><a href="sec_group_edit.php?name={$rows[id]->groupName}">Edit</a></td>
    		<td class="ItemDelete">
    			<span>
    				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id]->groupName}">
    			</span>
    		</td>
    	</tr>
    	{sectionelse}
    	<tr>
    		<td colspan="3" align="center">No security groups found</td>
    	</tr>
    	{/section}
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl"}
{include file="inc/footer.tpl"}