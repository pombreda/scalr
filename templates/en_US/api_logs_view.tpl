{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="search-ct"></div> 
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
            id: 'id',
            fields: [
				'id','transaction_id','dtadded','action','ipaddress','request'
            ]
        }),
        baseParams: {
        	sort: 'id',
        	dir: 'DESC'
        },
    	remoteSort: true,
		url: 'server/grids/api_log_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));
	
	 var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	 var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        id: 'api_logs_list_'+GRID_VERSION,
        title: "API logs {/literal}({$table_title_text}){literal}",
        height: 500,
        store: store,
        maximize: true,
        enableFilter: true,
        viewConfig: { 
        	emptyText: "No logs found"
        },
	    // Columns
        columns:[
			{header: "Transaction ID", width: 35, dataIndex: 'transaction_id', sortable: false},
			{header: "Time", width: 35, dataIndex: 'dtadded', sortable: false},
			{header: "Action", width: 15, dataIndex: 'action', sortable: false},
			{header: "IP address", width: 25, dataIndex: 'ipaddress', sortable: false}
		],

		// Row menu
    	rowOptionsMenu: [
      	             	
			{id: "option.details", 		text:'Details', 			  	href: "/api_log_entry_details.php?trans_id={transaction_id}"}
     	],
     	getRowOptionVisibility: function (item, record) {

			return true;
		}
    });

	grid.render();
    store.load();
});
{/literal}
</script>
{include file="inc/footer.tpl"}