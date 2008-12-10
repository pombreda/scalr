{include file="inc/header.tpl"}
	<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
	<script type="text/javascript" src="js/class.SelectControl.js"></script>
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
			<th>Applications</th>
			<th>Elastic IPs</th>
			<th>EBS</th>
			<td width="1%" nowrap></td>
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
		<td class="Item" valign="top">{$rows[id].sites} [<a href="sites_view.php?ami_id={$rows[id].ami_id}">View</a>]</td>
		<td class="Item" valign="top" align="center">{if $rows[id].use_elastic_ips == 1}<img src="/images/true.gif"> [<a href="elastic_ips.php?role={$rows[id].name}&farmid={$rows[id].farmid}">View</a>]{else}<img src="/images/false.gif">{/if}</td>
		<td class="Item" valign="top" align="center">{if $rows[id].use_ebs == 1}<img src="/images/true.gif"> [<a href="ebs_manage.php?role={$rows[id].name}&farmid={$rows[id].farmid}">View</a>]{else}<img src="/images/false.gif">{/if}</td>
		<td class="ItemDelete" valign="top">
			<a id="control_{$rows[id].id}" href="javascript:void(0)">Options</a>
		</td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id].id}';
    	var amiid = '{$rows[id].ami_id}';
    	var farmid = '{$farmid}';
    	var name = '{$rows[id].name}';
    	
    	var menu = [
    		{literal}{href: 'farm_stats.php?role='+name+"&farmid="+farmid, innerHTML: 'View statistics'},{/literal}
    		{literal}{href: 'farms_add.php?id='+farmid+'&ami_id='+amiid+'&configure=1', innerHTML: 'Configure'}{/literal}
            {if $farm_status == 1}
            {literal},{type: 'separator'}{/literal},
            {literal}{href: 'execute_script.php?ami_id='+amiid+'&farmid='+farmid, innerHTML: 'Execute script'}{/literal}
            {literal},{type: 'separator'}{/literal},
            {literal}{href: 'roles_view.php?farmid='+farmid+"&task=launch_new_instance&ami_id="+amiid, innerHTML: 'Launch new instance'}{/literal}
            {/if}
        ];
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	</script>
	{sectionelse}
	<tr>
		<td colspan="11" align="center">No roles found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="10" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
{include file="inc/footer.tpl"}