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
	        id: 'spotInstanceRequestId',	   
	        fields: [
				'spotInstanceRequestId' , 'spotPrice', 'type', 'state', 'createTime','instanceId','productDescription', 'imageId','instanceType','validFrom','validUntil'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_ec2_spotrequest_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "Spot requests",
        id: 'ec2_spotrequest_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No requests were found"
		},

        enableFilter: false,		
        
        tbar: 
        [ 
			 {
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Add new request',
				handler: function()
				{
					document.location.href = '/aws_ec2_amis_view.php';
				}
			 }
	    ],       
        // Columns      
        columns:
        [
        
			{header: "Request ID",		width: 40, dataIndex: 'spotInstanceRequestId',	sortable: false},
			{header: "Instance ID",		width: 40, dataIndex: 'instanceId',				sortable: false},
			{header: "Instance Type",	width: 40, dataIndex: 'instanceType',			sortable: false},
			{header: "Image ID",		width: 40, dataIndex: 'imageId',				sortable: false},
			{header: "Spot price",		width: 40, dataIndex: 'spotPrice',				sortable: false},
			{header: "Type",			width: 40, dataIndex: 'type',					sortable: false},			
			{header: "Create time",		width: 40, dataIndex: 'createTime',				sortable: false},
			{header: "Valid from",		width: 40, dataIndex: 'validFrom',				sortable: false},
			{header: "Valid until",		width: 40, dataIndex: 'validUntil',			sortable: false},			
			{header: "Description",		width: 40, dataIndex: 'productDescription',		sortable: false},
			{header: "State",			width: 40, dataIndex: 'state',					sortable: false}
			
		],

			
		withSelected: 
		{
			menu: 
			[
				{text: "Cancel request", value: "delete"}
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