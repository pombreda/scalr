{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">

var region = '{$smarty.session.aws_region}';

var regions = [
{foreach from=$regions name=id key=key item=item}
	['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
{/foreach}
];

{literal}
Ext.onReady(function () {
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'name',
	        
	        fields: [
				"engine", "status", "hostname", "port", "name", "username", "type", "storage",
				"dtadded", "avail_zone"
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_rds_instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "DB Instances",
        id: 'rds_instances_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No db instances found"
        },

        enableFilter: false,
                
        // Columns
        columns:[
			{header: "Name", width: 50, dataIndex: 'name', sortable: false},
			{header: "Hostname", width: 110, dataIndex: 'hostname', sortable: false},
			{header: "Port", width: 30, dataIndex: 'port', sortable: false},
			{header: "Status", width: 30, dataIndex: 'status', sortable: false},
			{header: "Username", width: 30, dataIndex: 'username', sortable: false}, 
			{header: "Type", width: 30, dataIndex: 'type', sortable: true, hidden:false},
			{header: "Storage", width: 20, dataIndex: 'storage', sortable: false},
			{header: "Placement", width: 30, dataIndex: 'avail_zone', sortable: false},
			{header: "Created at", width: 30, dataIndex: 'dtadded', sortable: false}
			
		],

		 tbar: [{text: 'Location:'}, new Ext.form.ComboBox({
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
		        listeners:
			        {	select:function(combo, record, index){
		        		store.baseParams.region = combo.getValue(); 
		        		store.load();
		        	}}
		    	}),

		    {
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Launch new DB instance',
				handler: function()
				{
					document.location.href = '/aws_rds_create_instance.php';
				}
			 }
		    ],		   
	
    	// Row menu
    	rowOptionsMenu: [
            {id: "option.details",			text: 'Details', 				href: "/aws_rds_instance_details.php?name={name}"}, 
            {id: "option.update",			text: 'Modify', 				href: "/aws_rds_instance_modify.php?name={name}"},       	
			new Ext.menu.Separator({id: "option.detailsSep"}),
			{id: "option.createSnap",		text: 'Create snapshot', 		href: "/aws_rds_snapshots.php?name={name}&action=create"},			
			{id: "option.autoSnap",			text: 'Auto snapshot settings', href: "/autosnapshots.php?name={name}"},
			{id: "option.snaps",			text: 'Manage snapshots', 		href: "/aws_rds_snapshots.php?name={name}"},
			new Ext.menu.Separator({id: "option.cwSep"}),
			{id: "option.cw",				text: 'CloudWatch monitoring',	href: "/aws_cw_monitor.php?ObjectId={name}&Object=DBInstanceIdentifier&NameSpace=AWS/RDS"},
			new Ext.menu.Separator({id: "option.snapsSep"}),
			{id: "option.events",			text: 'Events log', 			href: "/aws_rds_events_log.php?type=db-instance&name={name}"},
			new Ext.menu.Separator({id: "option.eventsSep"}),
			{id: "option.reboot",		text: 'Reboot', handler:function(menuItem){
				var Item = menuItem.parentMenu.record.data;
				SendRequestWithConfirmation(
					{
						action: 'RebootDBInstance', 
						instance: Item.name
					},
					'Reboot selected DB instance?',
					'Sending reboot command to db instance. Please wait...',
					'ext-mb-instance-rebooting',
					function(){
						grid.autoSize();
					},
					function(){
						store.load();
					},
					'/server/ajax-ui-server-aws-rds.php'
				);
			}},
            {id: "option.terminate",		text: 'Terminate', handler:function(menuItem){
				var Item = menuItem.parentMenu.record.data;
				SendRequestWithConfirmation(
					{
						action: 'TerminateDBInstance', 
						instance: Item.name
					},
					'Terminate selected DB instance?',
					'Sending terminate command to db instance. Please wait...',
					'ext-mb-instance-terminating',
					function(){
						grid.autoSize();
					},
					function(){
						store.load();
					},
					'/server/ajax-ui-server-aws-rds.php'
				);
			}}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			return true;
		},
		/*
		withSelected: {
			menu: [
				
			],
			hiddens: {with_selected : 1},
			action: "act"
		},
		*/
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