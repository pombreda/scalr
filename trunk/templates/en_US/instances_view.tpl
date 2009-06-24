{include file="inc/header.tpl"}
<br>
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">
{literal}
Ext.onReady(function () {
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'id',
	        remoteSort: true,
	
	        fields: [
				"role", "alias", "uptime", "public_ip", "is_elastic", "is_active", "private_ip",
				"is_rebooting", "can_use_ceip", "instance_index", "LA", "state", "instance_id",
				"ami_id", "type", "avail_zone", "custom_eip", "farmid", "id"
	        ]
    	}),
		url: '/server/grids/instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

	function sshRenderer(value, p, record) {
		if (record.data.state == 'Running')
			return '<a href="instances_view.php?action=sshClient&farmid='+record.data.farmid+'&instanceid='+record.data.instance_id+'" target="_blank" ><img style="margin-right:3px;" src="images/terminal.png"></a>';
		else
			return '<img src="/images/false.gif">';
	}

	function dnsRenderer(value, p, record) {
		if (value == 1)
			return '<img src="/images/true.gif">';
		else
			return '<img src="/images/false.gif">';
	}

	function ipRenderer(value, p, record) {

		var retval = "";
		
		if (record.data.is_elastic)
			retval += '<span style="color:green;vertical-align:middle;">';

		retval += value;

		if (record.data.is_elastic)
			retval += '</span>&nbsp;<img src="/images/icon_shelp.gif" style="vertical-align:middle;" title="Elastic IP">';

		return retval;
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Instances",
        id: 'instances_list3',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No instances found"
        },

        enableFilter: false,
		
        // Columns
        columns:[
			{header: "Farm role", width: 50, dataIndex: 'role', sortable: true},
			{header: "Instance ID", width: 30, dataIndex: 'instance_id', sortable: true},
			{header: "State", width: 30, dataIndex: 'state', sortable: false},
			{header: "Placement", width: 30, dataIndex: 'avail_zone', sortable: true}, 
			{header: "Type", width: 30, dataIndex: 'type', sortable: true, hidden:true},
			{header: "Uptime", width: 30, dataIndex: 'uptime', sortable: false},
			{header: "Load averages", width: 30, dataIndex: 'LA', sortable: false, align:'center', hidden:true},
			{header: "Public IP", width: 40, dataIndex: 'public_ip', renderer:ipRenderer, sortable: true},
			{header: "Private IP", width: 40, dataIndex: 'private_ip', sortable: false, hidden:true},
			{header: "SSH", width: 20, dataIndex: 'id', renderer:sshRenderer, sortable: false, align:'center'},
			{header: "Include in DNS zone", width: 30, dataIndex: 'is_active', renderer:dnsRenderer, sortable: false, align:'center'}
		],
		
    	// Row menu
    	rowOptionsMenu: [
            {id: "option.sync",			text: 'Synchronize to all', 				href: "/syncronize_role.php?iid={instance_id}"},        	
			new Ext.menu.Separator({id: "option.syncSep"}),
			{id: "option.console",		text: 'View console output', 				href: "/console_output.php?iid={instance_id}"},
			{id: "option.process",		text: 'View process list', 					href: "/process_list.php?iid={instance_id}&farmid={farmid}"},
			new Ext.menu.Separator({id: "option.procSep"}),
			{id: "option.dnsEx",		text: 'Exclude from DNS zone', 				href: "/instances_view.php?iid={instance_id}&farmid={farmid}&task=setinactive"},
			{id: "option.dnsIn",		text: 'Include in DNS zone', 				href: "/instances_view.php?iid={instance_id}&farmid={farmid}&task=setactive"},
			new Ext.menu.Separator({id: "option.dnsSep"}),
			{id: "option.disEip",		text: 'Disassociate Elastic IP', 			href: "/instance_eip.php?iid={instance_id}&task=unassign"},
			{id: "option.assocEip",		text: 'Associate Elastic IP', 				href: "/instance_eip.php?iid={instance_id}&task=assign"},
			{id: "option.attachEBS",	text: 'Attach EBS volume', 					href: "/ebs_manage.php?task=attach&instanceID={instance_id}"},
			new Ext.menu.Separator({id: "option.mysqlSep"}),
			{id: "option.mysql",		text: 'Backup/bundle MySQL data', 			href: "/farm_mysql_info.php?farmid={farmid}"},
			new Ext.menu.Separator({id: "option.execSep"}),
			{id: "option.exec",			text: 'Execute script', 					href: "/execute_script.php?farmid={farmid}&iid={instance_id}"},
			new Ext.menu.Separator({id: "option.menuSep"}),
			{id: "option.reboot",		text: 'Reboot', 							href: "/instances_view.php?iid={instance_id}&farmid={farmid}&task=reboot"},
			{id: "option.term",			text: 'Terminate', 							href: "/instances_view.php?iid={instance_id}&farmid={farmid}&task=terminate"},		
			new Ext.menu.Separator({id: "option.logsSep"}),
			{id: "option.logs",			text: 'View logs', 							href: "/logs_view.php?iid={instance_id}"}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (item.id == 'option.sync' || item.id == 'option.syncSep')
			{
				if (data.state != 'Pending terminate' && data.LA != 'Unknown' && data.is_rebooting != '1')
					return true;
				else
					return false;
			}
			else if (item.id == 'option.console' || item.id == 'option.process' || item.id == 'procSep')
			{
				if (data.state != 'Pending terminate' & data.is_rebooting != '1')
					return true;
				else
					return false;
			}
			else if (item.id == 'option.dnsEx' || item.id == 'option.dnsIn' || item.id == 'option.dnsSep')
			{
				if (data.state != 'Pending terminate' && ((item.id == 'option.dnsEx' && data.is_active == 1) || (item.id == 'option.dnsIn' && data.is_active == 0) || item.id == 'option.dnsSep'))
					return true;
				else
					return false;
			}
			else if (item.id == 'option.disEip' || item.id == 'option.assocEip')
			{
				if (data.state != 'Pending terminate' && data.can_use_ceip == 1)
				{
					if ((data.custom_eip && item.id == 'option.disEip') || (!data.custom_eip && item.id == 'option.assocEip'))
						return true;
					else
						return false;
				}
				else
					return false;
			}
			else if (item.id == 'option.attachEBS')
			{
				if (data.state != 'Pending terminate')
					return true;
				else
					return false;
			}
			else if (item.id == 'option.mysqlSep' || item.id == 'option.mysql')
			{
				if (data.state == 'Running' && data.alias == 'mysql')
					return true;
				else
					return false;
			}
			else if (item.id != 'option.logs')
			{
				if (data.state == 'Running')
					return true;
				else
					return false;
			}
			else if (item.id == 'option.logs')
				return true;
		},
		withSelected: {
			menu: [
				{text: "Reboot", value: "reboot"},
				{text: "Terminate", value: "terminate"}
			],
			hiddens: {with_selected : 1},
			action: "act"
		}
    });
    grid.render();
    store.load();

	return;
});
{/literal}
</script>

<!--
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
		<td class="Item" valign="top" nowrap></td>
		<td class="Item" align="center" valign="middle"></td>
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
 -->
{include file="inc/footer.tpl"}