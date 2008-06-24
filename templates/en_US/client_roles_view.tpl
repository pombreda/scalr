{include file="inc/header.tpl"}
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Role name</th>
			{if $smarty.session.uid == 0}<th>Client</th>{/if}
			<th>AMI</th>
			<th>Architecture</th>
			<th>Status</th>
			<th>Build date</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].name}</td>
		{if $smarty.session.uid == 0}<td class="Item" valign="top"><a href="clients_view.php?clientid={$rows[id].client.id}">{$rows[id].client.email}</a></td>{/if}
		<td class="Item" valign="top">{$rows[id].ami_id}</td>
		<td class="Item" valign="top">{$rows[id].architecture}</td>
		<td class="Item" valign="top">
		{if $rows[id].isreplaced && $rows[id].iscompleted != 2}
		  Being synchronized...
		{else}
		  {if $rows[id].iscompleted == 1}Active{elseif $rows[id].iscompleted == 0}Bundling...{else}Failed{/if}</td>
		{/if}
		<td class="Item" valign="top">{$rows[id].dtbuilt}</td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" {if $rows[id].iscompleted != 0 || $rows[id].isreplaced}{else}disabled{/if} id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="10" align="center">No custom roles found!</td>
	</tr>
	{/section}
	<tr>
		<td colspan="{if $smarty.session.uid == 0}6{else}5{/if}" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}
{include file="inc/footer.tpl"}