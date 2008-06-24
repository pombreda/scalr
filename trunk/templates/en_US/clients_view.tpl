{include file="inc/header.tpl"}
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>E-mail</th>
			<th>AWS Account Id</th>
			<th>Farms</th>
			<th>Instances</th>
			<th>Custom roles</th>
			<th>Farms limit</th>
			<th>Active</th>
			<th width="1%">Edit</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].email}</td>
		<td class="Item" valign="top">{$rows[id].aws_accountid}</td>
		<td class="Item" valign="top">{$rows[id].farms} [<a href="farms_view.php?clientid={$rows[id].id}">View</a>]</td>
		<td class="Item" valign="top">{$rows[id].instances}</td>
		<td class="Item" valign="top">{$rows[id].amis} [<a href="client_roles_view.php?clientid={$rows[id].id}">View</a>]</td>
		<td class="Item" valign="top">{if $rows[id].farms_limit == 0}Unlimited{else}{$rows[id].farms_limit}{/if}</td>
		<td class="Item" valign="top">{if $rows[id].isactive}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}</td>
		<td class="ItemEdit" valign="top"><a href="clients_add.php?id={$rows[id].id}">Edit</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="9" align="center">No clients found!</td>
	</tr>
	{/section}
	<tr>
		<td colspan="7" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 add_new=1}	
{include file="inc/footer.tpl"}