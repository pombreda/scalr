{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Farm role</th>
			<th>Instance ID</th>
			<th>State</th>
			<th>Placement</th>
			<th>Type</th>
			<th>Uptime</th>
			<th>Load averages</th>
			<th>Public IP</th>
			<th>SSH</th>
			<th>Include in DNS zone</th>
			<th width="1">Options</th>
			<td class="th" width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" nowrap><span title="{$rows[id]->instancesSet->item->imageId}">{$rows[id]->Role}</span></td>
		<td class="Item" valign="top" nowrap>{$rows[id]->instancesSet->item->instanceId}</td>
		<td class="Item" valign="top">{$rows[id]->State}</td>
		<td class="Item" valign="top" nowrap>{$rows[id]->instancesSet->item->placement->availabilityZone}</td>
		<td class="Item" valign="top">{$rows[id]->instancesSet->item->instanceType}</td>
		<td class="Item" valign="top">{$rows[id]->Uptime}</td>
		<td class="Item" valign="top">{$rows[id]->LA}</td>
		<td class="Item" valign="top" nowrap>{if $rows[id]->IsElastic}<span style="color:green;vertical-align:middle;">{/if}{$rows[id]->IP}{if $rows[id]->IsElastic}</span>&nbsp;<img src="/images/icon_shelp.gif" style="vertical-align:middle;" title="Elastic IP">{/if}</td>
		<td class="Item" align="center" valign="middle">{if $rows[id]->State == 'Running'}<a href="instances_view.php?action=sshClient&farmid={$farmid}&instanceid={$rows[id]->instancesSet->item->instanceId}" target="_blank" ><img style="margin-right:3px;" src="images/terminal.png"></a>{/if}</td>
		<td class="Item" align="center" valign="middle" nowrap>{if $rows[id]->IsActive}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}</td>
        <td class="ItemEdit" valign="top" width="1"><a id="control_{$rows[id]->instancesSet->item->instanceId}" href="javascript:void(0)">Options</a></td>
        <td class="ItemDelete">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id]->instancesSet->item->instanceId}">
			</span>
		</td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var iid = '{$rows[id]->instancesSet->item->instanceId}';
    	var farmid = '{$farmid}';
    	
    	var menu = [
            {if $rows[id]->instancesSet->item->instanceState->name == 'running' && $rows[id]->State != 'Pending terminate'}
            	{if $rows[id]->LA != 'Unknown' && $rows[id]->IsRebootLaunched == 0}{literal}{href: 'syncronize_role.php?iid='+iid, innerHTML: 'Synchronize to all'}{/literal},{/if}
            	{literal}{type: 'separator'},{/literal}
            	{if $rows[id]->IsRebootLaunched == 0}{literal}{href: 'console_output.php?iid='+iid, innerHTML: 'View console output'}{/literal},{/if}
            	{if $rows[id]->IsRebootLaunched == 0}{literal}{href: 'process_list.php?iid='+iid+"&farmid="+farmid, innerHTML: 'View process list'}{/literal},{/if}
            	{literal}{type: 'separator'},{/literal}
            	{if $rows[id]->IsActive == 1}
            		{literal}{href: 'instances_view.php?iid='+iid+'&task=setinactive&farmid='+farmid, innerHTML: 'Exclude from DNS zone'}{/literal},
            	{else}
            		{literal}{href: 'instances_view.php?iid='+iid+'&task=setactive&farmid='+farmid, innerHTML: 'Include in DNS zone'}{/literal},
            	{/if}
	            {if $rows[id]->canUseCustomEIPs}
	            	{if $rows[id]->customEIP}
	            		{literal}{href: 'instance_eip.php?iid='+iid+'&task=unassign', innerHTML: 'Disassociate Elastic IP'}{/literal},
	            	{else}
	            		{literal}{href: 'instance_eip.php?iid='+iid+'&task=assign', innerHTML: 'Associate Elastic IP'}{/literal},
	            	{/if}
	            {/if}
	            {literal}{href: 'ebs_manage.php?task=attach&instanceID='+iid, innerHTML: 'Attach EBS volume'}{/literal},
	        {/if}
	        {if $rows[id]->instancesSet->item->instanceState->name == 'running'}
	        	{if $rows[id]->Alias == 'mysql'}
	        		{literal}{type: 'separator'},{/literal}
	        		{literal}{href: 'farm_mysql_info.php?farmid='+farmid, innerHTML: 'Backup\/bundle MySQL data'}{/literal},
	        	{/if}
	        	{literal}{type: 'separator'},{/literal}
	        	
	        	{literal}{href: 'execute_script.php?farmid='+farmid+"&iid="+iid, innerHTML: 'Execute script'},{/literal}
	        	
	        	{literal}{type: 'separator'},{/literal}
	        	{literal}{href: 'instances_view.php?iid='+iid+'&task=reboot&farmid='+farmid, innerHTML: 'Reboot'}{/literal},
	        	{literal}{href: 'instances_view.php?iid='+iid+'&task=terminate&farmid='+farmid, innerHTML: 'Terminate'}{/literal},
	        	{literal}{type: 'separator'},{/literal}
	        {/if}
            {literal}{href: 'logs_view.php?iid='+iid, innerHTML: 'View logs'}{/literal}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+iid);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="14" align="center">No instances found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="10" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	<input type="hidden" name="farmid" value="{$farmid}" />
	{include file="inc/table_footer.tpl" colspan=9}
{include file="inc/footer.tpl"}