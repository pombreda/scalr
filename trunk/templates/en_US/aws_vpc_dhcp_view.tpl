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
				'id', 'options'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_vpc_dhcp_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "DHCP options",
        id: 'dhcp_options_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No DHCP options were found"
		},

        enableFilter: false,
           
         tbar: 
        [ 
			 {
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Add new option',
				handler: function()
				{
					document.location.href = '/aws_vpc_add_dhcp.php';
				}
			 }
	    ],   
                
        // Columns
        columns:
        [
			{header: "DHCP Options set ID", width: 50, dataIndex: 'id', sortable: false},
			{header: "Options", width: 50, dataIndex: 'options', sortable: false}			
			
		],

	
		// Row menu
    	rowOptionsMenu: 
        [      	             	
			{id: "option.CreateSubnet",       text: 'Configuration', 		href: "/aws_vpc_dhcp_config_view.php?id={id}"}
			
			
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