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
	        id: 'imageId',	   
	        fields: [
				'imageId', 'imageState', 'imageOwnerId', 'isPublic', 'architecture', 'imageType', 'rootDeviceType'
	        ]
    	}),
    	remoteSort: false,
		url: '/server/grids/aws_ec2_amis_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer(
	{
        renderTo: "maingrid-ct",
        height: 500,
        title: "AMIs for spot instances",
        id: 'ec2_amis_view_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: 
		{ 
        	emptyText: "No amies were found"
		},

        enableFilter: false,		
                
        // Columns
        columns:
        [
			{header: "AMI ID",		 width: 50, dataIndex: 'imageId',		sortable: false},
			{header: "State",		 width: 50, dataIndex: 'imageState',	sortable: false},
			{header: "Owner",		 width: 50, dataIndex: 'imageOwnerId',	sortable: false},
			{header: "Public",		 width: 50, dataIndex: 'isPublic',		sortable: false},
			{header: "Architecture", width: 50, dataIndex: 'architecture',	sortable: false},
			{header: "Type",		 width: 50, dataIndex: 'imageType',		sortable: false},			
			{header: "Device Type",	 width: 50, dataIndex: 'rootDeviceType',sortable: false}
			
		],

	
		// Row menu
    	rowOptionsMenu: 
        [      	             	
			{id: "option.SpotRequest",       text: 'Create spot instance request', 		href: "/aws_ec2_spotrequest_add.php?id={imageId}&arch={architecture}"}
			
     	],
     	getRowOptionVisibility: function (item, record) {

			return true;
		}
		
    });
    
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}