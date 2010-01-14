{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">

var FarmID = '{$smarty.get.farmid}';

var regions = [
{foreach from=$regions name=id key=key item=item}
	['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
{/foreach}
];

var region = '{$smarty.session.aws_region}';

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
	        	
	        fields: [
				"role", "farm", "alias", "uptime", "public_ip", "is_elastic", "is_active", "private_ip",
				"is_rebooting", "can_use_ceip", "instance_index", "LA", "state", "instance_id",
				"ami_id", "type", "avail_zone", "custom_eip", "farmid", "id", "farm_roleid", "region"
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

	function uptimeRenderer(value, p, record) {

		if (record.data.state == 'Terminated')
			return '<img src="/images/false.gif">';

		return value;
	}
		
	
	function sshRenderer(value, p, record) {

		if ((record.data.state == 'Running' || record.data.state == 'Initializing') && record.data.instance_index != '0')
			return '<a href="instances_view.php?action=sshClient&farmid='+record.data.farmid+'&instanceid='+record.data.instance_id+'" target="_blank" ><img style="margin-right:3px;" src="images/terminal.png"></a>';
		else
			return '<img src="/images/false.gif">';
	}

	function dnsRenderer(value, p, record) {
		if (value == 1 && record.data.state == 'Running' && record.data.farmid)
			return '<img src="/images/true.gif">';
		else
			return '<img src="/images/false.gif">';
	}

	function ipRenderer(value, p, record) {

		if (record.data.state == 'Terminated')
			return '<img src="/images/false.gif">';
		
		var retval = "";
				
		if (record.data.is_elastic && record.data.farmid)
			retval += '<span style="color:green;vertical-align:middle;">';

		retval += value;

		if (record.data.is_elastic && record.data.farmid)
			retval += '</span>&nbsp;<img src="/images/icon_shelp.png" style="vertical-align:middle;" title="Elastic IP">';

		return retval;
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Instances",
        id: 'instances_list1_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No instances found"
        },

        enableFilter: true,

        {/literal}
        {if $smarty.get.farmid}
        {literal}
	        tbar: ['&nbsp;&nbsp;', {xtype:'checkbox', boxLabel:'Don\'t show terminated instances', listeners:
		    	{check:function(item, checked){
			    	store.baseParams.hide_terminated = checked ? 'true' : 'false'; 
		        	store.load();
		    	}}
			}],
		{/literal}
        {else}
        {literal}
        	tbar: ['&nbsp;&nbsp;Location:', new Ext.form.ComboBox({
    			allowBlank: false,
    			editable: false, 
    	        store: regions,
    	        value: region,
    	        displayField:'state',
    	        typeAhead: false,
    	        mode: 'local',
    	        triggerAction: 'all',
    	        selectOnFocus:false,
    	        width:100,
    	        listeners:{select:function(combo, record, index){
    	        	store.baseParams.region = combo.getValue(); 
    	        	store.load();
    	        }}
    	    }), '-','&nbsp;&nbsp;', {xtype:'checkbox', boxLabel:'Don\'t show terminated instances', listeners:
	    	{check:function(item, checked){
		    	store.baseParams.hide_terminated = checked ? 'true' : 'false'; 
	        	store.load();
	    	}}
			}, '-','&nbsp;&nbsp;', {xtype:'checkbox', boxLabel:'Don\'t show non-scalr instances', listeners:
	    	{check:function(item, checked){
		    	store.baseParams.hide_non_scalr = checked ? 'true' : 'false'; 
	        	store.load();
	    	}}
		}],
       	{/literal}
        {/if}
        {literal}
                
        // Columns
        columns:[
			{header: "Farm", width: 50, dataIndex: 'farm', sortable: true},
			{header: "Role", width: 50, dataIndex: 'role', sortable: true},
			{header: "Instance ID", width: 30, dataIndex: 'instance_id', sortable: true},
			{header: "State", width: 30, dataIndex: 'state', sortable: false},
			{header: "Placement", width: 30, dataIndex: 'avail_zone', sortable: true}, 
			{header: "Type", width: 30, dataIndex: 'type', sortable: true, hidden:true},
			{header: "Uptime", width: 30, dataIndex: 'uptime', sortable: false, renderer:uptimeRenderer},
			{header: "LA", width: 15, dataIndex: 'LA', sortable: false, align:'center', hidden:true},
			{header: "Public IP", width: 40, dataIndex: 'public_ip', renderer:ipRenderer, sortable: true},
			{header: "Private IP", width: 40, dataIndex: 'private_ip', sortable: false, hidden:true},
			{header: "SSH", width: 20, dataIndex: 'id', renderer:sshRenderer, sortable: false, align:'center'},
			{header: "Include in DNS zone", width: 30, dataIndex: 'is_active', renderer:dnsRenderer, sortable: false, align:'center'}
		],

	
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.info",			text: 'Extended instance information', 		href: "/aws_ec2_instance_info.php?iid={instance_id}&region={region}"},
			new Ext.menu.Separator({id: "option.infoSep"}),
            {id: "option.sync",			text: 'Synchronize to all', 				href: "/syncronize_role.php?iid={instance_id}"},        	
			new Ext.menu.Separator({id: "option.syncSep"}),
			{id: "option.editRole",		text: 'Configure role in farm', 			href: "/farms_add.php?id={farmid}&ami_id={ami_id}&configure=1&return_to=instances_list"},        				
			new Ext.menu.Separator({id: "option.procSep"}),
			{id: "option.dnsEx",		text: 'Exclude from DNS zone', 				href: "/instances_view.php?iid={instance_id}&farmid={farmid}&task=setinactive"},
			{id: "option.dnsIn",		text: 'Include in DNS zone', 				href: "/instances_view.php?iid={instance_id}&farmid={farmid}&task=setactive"},
			new Ext.menu.Separator({id: "option.dnsSep"}),
			{id: "option.disEip",		text: 'Disassociate Elastic IP', 			href: "/instance_eip.php?iid={instance_id}&task=unassign"},
			{id: "option.assocEip",		text: 'Associate Elastic IP', 				href: "/instance_eip.php?iid={instance_id}&task=assign"},
			{id: "option.attachEBS",	text: 'Attach EBS volume', 					href: "/ebs_manage.php?task=attach&instanceID={instance_id}"},
			new Ext.menu.Separator({id: "option.editRoleSep"}),
			{id: "option.console",		text: 'View console output', 				href: "/console_output.php?iid={instance_id}"},
			{id: "option.process",		text: 'View process list', 					href: "/process_list.php?iid={instance_id}&farmid={farmid}"},
			{id: "option.messaging",	text: 'Scalr internal messaging', 			href: "/scalr_i_messages.php?iid={instance_id}&farmid={farmid}"},
			new Ext.menu.Separator({id: "option.mysqlSep"}),
			{id: "option.mysql",		text: 'Backup/bundle MySQL data', 			href: "/farm_mysql_info.php?farmid={farmid}"},
			new Ext.menu.Separator({id: "option.execSep"}),
			{id: "option.exec",			text: 'Execute script', 					href: "/execute_script.php?farmid={farmid}&iid={instance_id}"},
			new Ext.menu.Separator({id: "option.menuSep"}),
			{id: "option.reboot",		text: 'Reboot', handler:function(menuItem){
				var Item = menuItem.parentMenu.record.data;
				SendRequestWithConfirmation(
					{
						action: 'RebootInstances', 
						instances: Ext.encode([Item.instance_id]),
						farmid: Item.farmid
					},
					'Reboot selected instance(s)?',
					'Sending reboot command to instance(s). Please wait...',
					'ext-mb-instance-rebooting',
					function(){
						grid.autoSize();
					},
					function(){
						store.load();
					}
				);
			}},

			{id: "option.term",	text: 'Terminate', 	handler:function(menuItem){
				var Item = menuItem.parentMenu.record.data;
				window.TID = false;
				window.TIF = false;
				SendRequestWithConfirmation(
					{
						action: 'TerminateInstances', 
						instances: Ext.encode([Item.instance_id]),
						farmid: Item.farmid
					},
					'Terminate selected instance(s)?'+
					'<br \><br \>'+
					'<input type="checkbox" onclick="window.TID = this.checked;"> Decrease \'Mininimum instances\' setting<br \>' +
					'<input type="checkbox" onclick="window.TIF = this.checked;"> Forcefully terminate selected instance(s)<br \>',
					'Terminating instance(s). Please wait...',
					'ext-mb-instance-terminating',
					function(){
						grid.autoSize();
					},
					function(){
						store.load();
					}
				);
			}},		

			new Ext.menu.Separator({id: "option.logsSep"}),
			{id: "option.logs",			text: 'View logs', 							href: "/logs_view.php?iid={instance_id}"}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (data.farmid)
			{
				if (item.id == 'option.info' || item.id == 'option.infoSep')
					return true;
				else if (item.id == 'option.sync' || item.id == 'option.syncSep')
				{
					if (data.state != 'Pending terminate' && data.LA != 'Unknown' && data.is_rebooting != '1')
						return true;
					else
						return false;
				}
				else if (item.id == 'option.console' || item.id == 'option.process' || item.id == 'procSep')
				{
					if (data.state != 'Pending terminate' && data.is_rebooting != '1')
						return true;
					else
						return false;
				}
				else if (item.id == 'option.dnsEx' || item.id == 'option.dnsIn' || item.id == 'option.dnsSep')
				{
					if (data.state != 'Pending terminate' && data.state != 'Pending' && ((item.id == 'option.dnsEx' && data.is_active == 1) || (item.id == 'option.dnsIn' && data.is_active == 0) || item.id == 'option.dnsSep'))
						return true;
					else
						return false;
				}
				else if (item.id == 'option.disEip' || item.id == 'option.assocEip')
				{
					if (data.state != 'Pending terminate' && data.can_use_ceip == 1 && data.state != 'Pending')
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
					if (data.state != 'Pending terminate' && data.state != 'Pending')
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
				else if (item.id == 'option.messaging')
					return true;
				else if (item.id != 'option.logs')
				{
					if (data.state == 'Running' || item.id == 'option.term' || item.id == 'option.menuSep')
						return true;
					else
						return false;
				}
				else if (item.id == 'option.logs')
					return true;
			}
			else
			{
				if (item.id == 'option.console' || item.id == 'option.reboot' || item.id == 'option.term')
					return true;
				else
					return false;
			}
		},
		withSelected: {
			menu: [
				{text: "Reboot", value: "reboot", handler:function(){

					var instances = grid.getSelectionModel().selections.keys;

					SendRequestWithConfirmation(
						{
							action: 'RebootInstances', 
							instances: Ext.encode(instances),
							farmid: FarmID
						},
						'Reboot selected instance(s)?',
						'Sending reboot command to instance(s). Please wait...',
						'ext-mb-instance-rebooting',
						function(){
							grid.autoSize();
						},
						function(){
							store.load();
						}
					);
					
				}},
				{text: "Terminate", value: "terminate", handler:function(){

					var instances = grid.getSelectionModel().selections.keys;

					SendRequestWithConfirmation(
						{
							action: 'TerminateInstances', 
							instances: Ext.encode(instances),
							farmid: FarmID
						},
						'Terminate selected instance(s)?',
						'Terminating instance(s). Please wait...',
						'ext-mb-instance-terminating',
						function(){
							grid.autoSize();
						},
						function(){
							store.load();
						}
					);
					
				}}
			],
			hiddens: {with_selected : 1},
			action: "act"
		},
		listeners: {
			beforeshowoptions: function (grid, record, romenu, ev) {
				romenu.record = record;
			}
		}
    });
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}