{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">

var FarmID = '{$smarty.request.farmid}';

var regions = [
{section name=id loop=$regions}
	['{$regions[id]}','{$regions[id]}']{if !$smarty.section.id.last},{/if}
{/section}
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
				"messageid", "server_id", "status", "handle_attempts", "dtlasthandleattempt","message_type","type","isszr"
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/scalr_i_msg_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

    function typeRenderer(value, p, record)
    {
    	return record.data.type+" / "+value;
    }

    function serverRenderer(value, p, record)
    {
    	return '<a href="/server_view_extended_info.php?server_id='+value+'">'+value+'</a>';
    }
    
    function statusRendered(value, p, record) {

		if (record.data.status == 1)
			return '<span style="color:green;">Delivered</span>';
		else if (record.data.status == 0)
			return '<span style="color:orange;">Delivering...</span>';
		else if (record.data.status == 2 || record.data.status == 3)
			return '<span style="color:red;">Failed</span>';
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Messages",
        id: 'scalr_msgs_list1_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No messages found"
        },

        enableFilter: true,
                
        // Columns
        columns:[
			{header: "Message ID", width: 50, dataIndex: 'messageid', sortable: true},
			{header: "Message type", width: 40, dataIndex: 'message_type', renderer:typeRenderer, sortable: false},
			{header: "Server ID", width: 30, dataIndex: 'server_id', renderer:serverRenderer, sortable: true},
			{header: "Status", width: 30, dataIndex: 'isdelivered', renderer:statusRendered, sortable: true},
			{header: "Delivery attempts", width: 15, dataIndex: 'handle_attempts', sortable: true}, 
			{header: "Last delivery attempt", width: 50, dataIndex: 'dtlasthandleattempt', sortable: true}
		],

		rowOptionsMenu: [
   			{id: "option.edit", 		text:'Re-send message', 			  	href: "/scalr_i_messages.php?server_id={server_id}&action=resend&message={messageid}"}
        ],

        getRowMenuVisibility: function (record) {
			return (record.data.status == 2 || record.data.status == 3);
		},
        
        getRowOptionVisibility: function (item, record) {
   			var data = record.data;

			return true;
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