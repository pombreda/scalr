{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl" filter=0 paging=""}
    {include file="inc/intable_header.tpl" header="Search" color="Gray"}
        <tr>
			<td nowrap="nowrap">Search string:</td>
			<td><input type="text" name="search" class="text" id="search" value="{$search}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Farm:</td>
			<td>
				<select name="farmid">
					<option></option>
					{section name=id loop=$farms}
					<option {if $farmid == $farms[id].id}selected{/if} value="{$farms[id].id}">{$farms[id].name}</option>
					{/section}
				</select>
			</td>
		</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {include file="inc/table_footer.tpl" colspan=9 button2=1 button2_name="Search"}
    <br>
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" width="100%" id="Webta_Items">
	<thead>
		<tr>
			<th>Severity</th>
			<th>Instance</th>
			<th>Source</th>
			<th>Time</th>
			<th>Message</th>
			<!--
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
			-->
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
	
		<td class="Item" valign="top" nowrap>{$rows[id].severity}</td>
		<td class="Item" valign="top" nowrap><a href="/instances_view.php?iid={$rows[id].servername}&farmid={$rows[id].farmid}">{$rows[id].servername}</a></td>
		<td class="Item" valign="top" nowrap>{$rows[id].source}</td>
		<td class="Item" valign="top" nowrap>{$rows[id].time}</td>
		<td class="Item" valign="top">{$rows[id].message|nl2br}</td>
		<!--
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" id="actid[]" name="actid[]" value="{$rows[id].id}">
			</span>
		</td>
		-->
	</tr>
	{sectionelse}
	<tr>
		<td colspan="5" align="center">No log entries found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="5" align="center">&nbsp;</td>
		<!--<td class="ItemDelete" valign="top">&nbsp;</td>-->
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
		{include file="inc/footer.tpl"}