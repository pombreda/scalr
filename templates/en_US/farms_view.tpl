{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
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
			<th>Action</th>
			<th width="1%">Options</th>
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
		<td class="Item" valign="top" nowrap><a href="farms_control.php?farmid={$rows[id].id}">{if $rows[id].status == 1}Terminate{else}Launch{/if}</a></td>		
		<td class="ItemEdit" valign="top"><a id="control_{$rows[id].id}" href="javascript:void(0)">Options</a></td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id].id}';
    	var name = '{$rows[id].name}';
    	var control_action= '{if $rows[id].status == 1}Terminate{else}Launch{/if}';
    	
    	var menu = [
            {literal}{href: '/storage/keys/'+id+'/'+name+'.pem', innerHTML: 'Download Private key'}{/literal},
            {literal}{type: 'separator'}{/literal},
            {if $rows[id].status == 1}
            {literal}{href: 'farm_stats.php?farmid='+id, innerHTML: 'View statistics'}{/literal},
            {/if}
            {literal}{href: 'events_view.php?farmid='+id, innerHTML: 'Events & Notifications'}{/literal},
            {literal}{type: 'separator'}{/literal},
            {literal}{href: 'farms_control.php?farmid='+id, innerHTML: control_action}{/literal},
            {literal}{type: 'separator'}{/literal},
            {literal}{href: 'farms_add.php?id='+id, innerHTML: 'Edit'}{/literal},
            {literal}{href: 'farm_delete.php?id='+id, innerHTML: 'Delete'}{/literal}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="{if $smarty.session.uid == 0}8{else}7{/if}" align="center">No farms found!</td>
	</tr>
	{/section}
	<tr>
		<td colspan="{if $smarty.session.uid == 0}7{else}6{/if}" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
{include file="inc/footer.tpl"}