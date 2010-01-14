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
	        id: 'id',	   
	        fields: [
				'id', 'state', 'cidrBlock', 'dhcpOptionsId'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_vpc_instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "Your Virtual Private Cloud",
        id: 'vpc_instances_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No VPC clouds were found"
		},

        enableFilter: false,
           
         tbar: 
        [ 
			 {
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Add new VPC',
				handler: function()
				{
					document.location.href = '/aws_vpc_add.php';
				}
			 }
	    ],   
                
        // Columns
        columns:
        [
			{header: "VPC ID", width: 70, dataIndex: 'id', sortable: false},
			{header: "CIDR", width: 70, dataIndex: 'cidrBlock', sortable: false},
			{header: "State", width: 70, dataIndex: 'state', sortable: false},
			{header: "DHCP Options", width: 80, dataIndex: 'dhcpOptionsId', sortable: false}
			
		],

	
		// Row menu
    	rowOptionsMenu: 
        [      	             	
			{id: "option.CreateSubnet",       text: 'Create a Subnet', 		href: "/aws_vpc_add_subnet.php?id={id}"},
			{id: "option.attachVpnGateway",   text: 'Attach a VPN Gateway', href: "/aws_vpc_attach_vpn_gateway.php?id={id}"},
			{id: "option.setDhcpOptions",  	  text: 'Set DHCP Options', 	href: "/aws_vpc_attach_dhcp.php?id={id}"}
     	],
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