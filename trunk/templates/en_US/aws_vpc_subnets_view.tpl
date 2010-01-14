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
    var store = new Ext.ux.scalr.Store
    ({
    	reader: new Ext.ux.scalr.JsonReader
    	({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'id',	   
	        fields: 
	        [
				'id', 'vpcId', 'state', 'cidrBlock', 'availableIpAddressCount', 'availabilityZone'
			]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_vpc_subnet_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "Subnet list",
        id: 'vpc_subnet_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No VPC subnets were found"
		},

        enableFilter: false,
             
            
        // Columns
        columns:
        [
			{header: "Subnet ID", width: 60, dataIndex: 'id', sortable: false},
			{header: "VPC ID", width: 60, dataIndex: 'vpcId', sortable: false},
			{header: "CIDR", width: 60, dataIndex: 'cidrBlock', sortable: false},
			{header: "State", width: 60, dataIndex: 'state', sortable: false},
			{header: "Available IPs", width: 80, dataIndex: 'availableIpAddressCount', sortable: false},
			{header: "Available Zone", width: 80, dataIndex: 'availabilityZone', sortable: false}
			
		],

	
		// Row menu
    	/*rowOptionsMenu: 
        [      	             	
			
			{id: "option.eventsSubnet",       text: 'Events log', href: ""}
			
     	],*/
     	getRowOptionVisibility: function (item, record) {

			return true;
		},
		withSelected: 
		{
			menu: 
			[
				{text: "Delete", value: "delete"}
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