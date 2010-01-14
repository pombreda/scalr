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
	        id: '',	   
	        fields: [
				'type', 'price', 'description', 'timestamp'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_ec2_pricehistory_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "Price history",
        id: 'ec2_pricehistory_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No price history were found"
		},

        enableFilter: true,		
                
        // Columns
        columns:
        [
			{header: "Instance type",	width: 70, dataIndex: 'type',		sortable: true},
			{header: "Spot price",		width: 70, dataIndex: 'price',		sortable: true},
			{header: "Timestamp",		width: 70, dataIndex: 'timestamp',	sortable: true},
			{header: "Description",		width: 80, dataIndex: 'description',sortable: false}
			
		]		
    });
    
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}