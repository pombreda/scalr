{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
<script type="text/javascript">

var uid = '{$smarty.session.uid}';

var regions = [
{foreach from=$regions name=id key=key item=item}
	['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
{/foreach}
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
				'id','name','storage','idtcreated','avail_zone','engine','status','port','dtcreated'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/aws_rds_snapshots_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "DB snapshots",
        id: 'db_snapshots_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No db snapshots found"
        },

        enableFilter: true,

		tbar: ['Region:', new Ext.form.ComboBox({
			allowBlank: false,
			editable: false, 
	        store: regions,
	        value: region,
	        displayField:'state',
	        typeAhead: false,
	        mode: 'local',
	        triggerAction: 'all',
	        selectOnFocus:false,
	        width:100,
	        listeners:{select:function(combo, record, index){
	        	store.baseParams.region = combo.getValue(); 
	        	store.load();
	        }}
	    })
		],
		
	
        // Columns
        columns:[
			{header: "Name", width: 70, dataIndex: 'name', sortable: false},
			{header: "Storage", width: 25, dataIndex: 'storage', sortable: false},
			{header: "Created at", width: 50, dataIndex: 'dtcreated', sortable: false},
			{header: "Instance created at", width: 50, dataIndex: 'idtcreated', sortable: false},
			{header: "Status", width: 50, dataIndex: 'status', sortable: false},
			{header: "Port", width: 50, dataIndex: 'port', sortable: false},
			{header: "Placement", width: 50, dataIndex: 'avail_zone', sortable: false},
			{header: "Engine", width: 50, dataIndex: 'engine', sortable: false}
		],

		// Row menu
    	rowOptionsMenu: [
            {id: "option.launch",	text: 'Restore DB instance from this snapshot', 	href: "/aws_rds_create_instance.php?snapshot={id}"}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			return true;
		},
		
    	// Row menu
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