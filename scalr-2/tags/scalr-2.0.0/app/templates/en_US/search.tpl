{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">
var gridData = {$grid_data};

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
	        fields: ['title', 'href', 'count']
	    }),
	    remoteSort: true,
		url: '/server/grids/farms_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
    store.loadData(gridData);

	function renderTitle (value, p, record) {
		return '<a href="'+record.data.href+'">'+value+'</a>';
	} 
	
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Search results",
        id: 'search_results',
        store: store,
        maximize: true,
        viewConfig: { emptyText: "Not found" },

        // Columns
        columns:[
			{header: "Page", width: 75, dataIndex: 'title', renderer: renderTitle},
			{header: "Total results", width: 25, dataIndex: 'count'}
		],
		enableFilter: false
    });
    grid.render();
    store.loadData(gridData);

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}