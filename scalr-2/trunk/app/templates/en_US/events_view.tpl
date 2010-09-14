{include file="inc/header.tpl"}	
	<div style="float:right;margin-right:20px;padding:0px;margin:5px;">
		<img src="/images/dhtmlxtree/csh_vista/folderOpen_scripting.gif" style="vertical-align:middle;margin:0px;padding:0px;"><a style="margin:0px;padding:0px;" href="configure_event_notifications.php?farmid={$farminfo.id}">Configure / RSS Feed</a>
	</div>
	<div style="clear:both"></div>

	<link rel="stylesheet" href="css/grids.css" type="text/css" />
	<div id="maingrid-ct" class="ux-gridviewer"></div>
	<script type="text/javascript">
	var uid = '{$smarty.session.uid}';	
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
					'id','dtadded', 'type', 'message'
		        ]
	    	}),
	    	remoteSort: true,
			url: '/server/grids/farm_events_list.php?a=1{/literal}{$grid_query_string}{literal}',
			listeners: { dataexception: Ext.ux.dataExceptionReporter }
	    });
				
	    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
		var grid = new Ext.ux.scalr.GridViewer({
	        renderTo: "maingrid-ct",
	        height: 500,
	        title: "Events list {/literal}({$table_title_text}){literal}",
	        id: 'farm_events_list_'+GRID_VERSION,
	        store: store,
	        maximize: true,
	        viewConfig: { 
	        	emptyText: "No events found"
	        },
	        			
	        // Columns
	        columns:[
				{header: "Date", width: 80, dataIndex: 'dtadded', sortable: false},
				{header: "Event", width: 50, dataIndex: 'type', sortable: false},
				{header: "Description", width: 300, dataIndex: 'message', sortable: false}
			]
	    });
	    
	    grid.render();
	    store.load();
	
		return;
	});
	{/literal}
	</script>	
{include file="inc/footer.tpl"}