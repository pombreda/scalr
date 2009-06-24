{include file="inc/header.tpl"}
<br />
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
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
	        remoteSort: true,
	
	        fields: [
				{name: 'id', type: 'int'},
				'farmid', 'farmname', 'ami_id', 'rolename', 'scriptname', 'event_name'
	        ]
    	}),
		url: '/server/grids/script_shortcuts_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
		
	function targetRenderer(value, p, record) {
		var retval = '<a href="farms_view.php?id='+record.data.farmid+'">'+record.data.farmname+'</a>';
		if (record.data.ami_id)
			retval += '&rarr;<a href="roles_view.php?farmid='+record.data.farmid+'&ami_id='+record.data.ami_id+'">'+record.data.rolename+'</a>';

		retval += '&nbsp;&nbsp;&nbsp;';

		return retval;
	}

	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Script shortcuts",
        id: 'scripts_list2',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No shortcuts defined"
        },

        // Columns
        columns:[
			{header: "Target", width: 150, dataIndex: 'id', renderer:targetRenderer, sortable: false},
			{header: "Script", width: 500, dataIndex: 'scriptname', sortable: true}
		],

    	// Row menu
    	rowOptionsMenu: [
			{id: "option.edit", 		text: 'Edit', 	href: "/execute_script.php?script={event_name}&task=edit&farmid={farmid}"}
     	],

     	getRowOptionVisibility: function (item, record) {
			return true;
		},

		getRowMenuVisibility: function (record) {
			return true;
		},
		withSelected: {
			menu: [
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