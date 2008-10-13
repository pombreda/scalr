{include file="inc/header.tpl"}
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Role name</th>
			<th>Placement</th>
			<th>Min instances</th>
			<th>Max instances</th>
			<th>Min LA</th>
			<th>Max LA</th>
			<th>Running instances</th>
			<th>Pending instances</th>
			<th>Applications</th>
			<th>Elastic IPs</th>
			<th>Statistics</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].name}</td>
		<td class="Item" valign="top">{$rows[id].avail_zone}</td>
		<td class="Item" valign="top">{$rows[id].min_count}</td>
		<td class="Item" valign="top">{$rows[id].max_count}</td>
		<td class="Item" valign="top">{$rows[id].min_LA}</td>
		<td class="Item" valign="top">{$rows[id].max_LA}</td>
		<td class="Item" valign="top">{$rows[id].r_instances} [<a href="instances_view.php?state=Running&farmid={$rows[id].farmid}">View</a>]</td>
		<td class="Item" valign="top">{$rows[id].p_instances} [<a href="instances_view.php?state=Pending&farmid={$rows[id].farmid}">View</a>]</td>
		<td class="Item" valign="top">{$rows[id].sites} [<a href="sites_view.php?ami_id={$rows[id].ami_id}">View</a>]</td>
		<td class="Item" valign="top" align="center">{if $rows[id].use_elastic_ips == 1}<img src="/images/true.gif"> [<a href="elastic_ips.php?role={$rows[id].name}&farmid={$rows[id].farmid}">View</a>]{else}<img src="/images/false.gif">{/if}</td>
		<td class="Item" valign="top"><a href="farm_stats.php?role={$rows[id].name}&farmid={$farmid}">View</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" {if $farm_status != 1}disabled{/if} id="delete[]" name="delete[]" value="{$rows[id].ami_id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="13" align="center">No roles found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="11" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}	
{include file="inc/footer.tpl"}