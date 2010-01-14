{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">

var FarmID = '{$smarty.get.farmid}';

var regions = [
{section name=id loop=$regions}
	['{$regions[id]}','{$regions[id]}']{if !$smarty.section.id.last},{/if}
{/section}
];

var region = '{$smarty.session.aws_region}';

{literal}
Ext.onReady(function () {

Ext.QuickTips.init();
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'iid',	   
	        fields: [
				'iid', 'imageId', 'instanceState','dnsName','keyName','instanceType','launchTime',
				'availabilityZone','monState','instanceLifecycle'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_ec2_instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "Running spot instances",
        id: 'ec2_instances_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No running spot instances were found"
		},

        enableFilter: false,	
        
        
        // Columns
        columns:
        [			
			{header: "Instance ID",			width: 40, dataIndex: 'iid',				sortable: false},
			{header: "Image ID",			width: 40, dataIndex: 'imageId',			sortable: false},
			{header: "Instance state",		width: 40, dataIndex: 'instanceState',		sortable: false},
			{header: "instance type",		width: 40, dataIndex: 'instanceType',		sortable: false},
			{header: "DNS name",			width: 40, dataIndex: 'dnsName',			sortable: false},			
			{header: "availability zone",	width: 40, dataIndex: 'availabilityZone',	sortable: false},
			{header: "Lifecycle",			width: 40, dataIndex: 'instanceLifecycle',	sortable: false}	
			
		],
		
		
		// Row menu
    	rowOptionsMenu: 
        [    
			{id: "option.details",	text: 'Details', 	href: "/aws_ec2_instance_info.php?iid={iid}"}
     	],
     	getRowOptionVisibility: function (item, record) {

			return true;
		},
		withSelected: 
		{
			menu: 
			[
				{text: "Delete", value: "delete" }
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
{include file="inc/footer.tpl"}