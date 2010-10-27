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
				'id', 'instance_type', 'avail_zone', 'duration', 
				'usage_price', 'fixed_price', 'instance_count', 'description', 'state'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/reserved_instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
    	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Reserved instances",
        id: 'reserved_instances_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        enableFilter: false,
        viewConfig: { 
        	emptyText: "No reserved instances found"
        },

        tbar: [{text: 'Location:'}, new Ext.form.ComboBox({
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
	    })],
        
        // Columns
        columns:[
			{header: "ID", width: 115, dataIndex: 'id', sortable: true},
			{header: "Type", width: 35, dataIndex: 'instance_type', sortable: false},
			{header: "Placement", width: 35, dataIndex: 'avail_zone', sortable: true},
			{header: "Duration", width: 35, dataIndex: 'duration', renderer:function(value, p, record){ return (value == 1) ? value+" year" : value+" years"; }, sortable: false, align:'center'},
			{header: "Usage Price", width: 40, dataIndex: 'usage_price', renderer:function(value, p, record){ return '$'+value; }, sortable: false, align:'center'},
			{header: "Fixed Price", width: 35, dataIndex: 'fixed_price', renderer:function(value, p, record){ return '$'+value; }, sortable: false, align:'center'},
			{header: "Count", width: 25, dataIndex: 'instance_count', sortable: false, align:'center'},
			{header: "Description", width: 50, dataIndex: 'description', sortable: false},
			{header: "State", width: 50, dataIndex: 'state', sortable: false}
		]
    });
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}