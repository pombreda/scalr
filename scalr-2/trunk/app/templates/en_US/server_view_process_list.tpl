{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">
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
	        id: 'hrSWRunName',
	        	
	        fields: [
				'hrSWRunName',
				'hrSWRunPath',
				'hrSWRunParameters',
				'hrSWRunType',
				'hrSWRunStatus',
				'hrSWRunPerfCPU',
				'hrSWRunPerfMem'
	       ]
    	}),
    	remoteSort: true,
		url: '/server/grids/processes_list.php?a=1{/literal}{$grid_query_string}&server_id={$server_id}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

	function nameRenderer(value, p, record) {
		return record.data.hrSWRunName+" "+record.data.hrSWRunParameters;
	}
		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Process list",
        id: 'process_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No processes found"
        },

        enableFilter: false,
        enablePaging: false,
	
        // Columns
        columns:[
			{header: "Process", width: 200, dataIndex: 'hrSWRunName', renderer:nameRenderer, sortable: false},
			{header: "RAM Usage", width: 50, dataIndex: 'hrSWRunPerfMem', sortable: false},
			{header: "Type", width: 60, dataIndex: 'hrSWRunType', sortable: false},
			{header: "Status", width: 40, dataIndex: 'hrSWRunStatus', sortable: false}
		]
    });
    
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}