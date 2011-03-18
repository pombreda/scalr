{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
	<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
	<thead>
		<th>Host (IP)</th>
		<th>Port</th>
		<th>Username</th>
		<th>Path to rndc</th>
		<th>Path to zone files folder</th>
		<th>Path to named.conf</th>
		<th>Proxy NS</th>
		<th>Backup NS</th>
		<td class="th" nowrap width="1%">Edit</th>
		<td class="th" width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr bgcolor="#F9F9F9">
		<td class="Item" valign="top">{$rows[id].host} ({$rows[id].ipaddress})</td>
		<td class="Item" valign="top">{$rows[id].port}</td>
		<td class="Item" valign="top">{$rows[id].username}</td>
		<td class="Item" valign="top">{$rows[id].rndc_path}</td>
		<td class="Item" valign="top">{$rows[id].named_path}</td>
		<td class="Item" valign="top">{$rows[id].namedconf_path}</td>
		<td class="Item" valign="top" align="center">{if $rows[id].isproxy}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}</td>
		<td class="Item" valign="top" align="center">{if $rows[id].isbackup}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}</td>
		<td class="ItemEdit" valign="top"><a href="ns_add.php?id={$rows[id].id}">Edit</a></td>
		<td class="ItemDelete">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="11" align="center">No nameservers found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="8" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 page_data_options_add=1}
{include file="inc/footer.tpl"}