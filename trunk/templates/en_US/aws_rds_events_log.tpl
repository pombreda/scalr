{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">
{literal}
Ext.onReady(function () {
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'message',
	        	
	        fields: [
	            'time', 'message', 'source', 'type'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_rds_event_log_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Events",
        id: 'aws_rds_events_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No events found"
        },

        // Columns
        columns:[
			{header: "Time", width: 100, dataIndex: 'time', sortable: false},
			{header: "Message", width: 100, dataIndex: 'message',  sortable: false},
			{header: "Source", width: 100, dataIndex: 'source',  sortable: false},
			{header: "Type", width: 50, dataIndex: 'type', sortable: false}
		]

    });
    grid.render();
    store.load();
});
{/literal}
</script>
{include file="inc/footer.tpl"}