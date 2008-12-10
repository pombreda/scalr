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
			<th>Time</th>
			<th>Event</th>
			<th>Farm</th>
			<th>Target</th>
			<th>Message</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" nowrap>{$rows[id].dtadded}</td>
		<td class="Item" valign="top" nowrap>{if $rows[id].event}On{$rows[id].event}{/if}</td>
		<td class="Item" valign="top" nowrap><a href="farms_view.php?id={$rows[id].farm.id}">{$rows[id].farm.name}</a></td>
		<td class="Item" valign="top" nowrap><a href="instances_view.php?farmid={$rows[id].farm.id}&iid={$rows[id].instance}">{$rows[id].instance}</a></td>
		<td class="Item" valign="top">{$rows[id].message|nl2br}</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="5" align="center">No log entries found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="5" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
		{include file="inc/footer.tpl"}