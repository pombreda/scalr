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
				'id','name','description'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/sec_groups_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Security groups",
        id: 'sgroups_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No security groups found"
        },

        enableFilter: true,
        
		tbar: ['Location:', new Ext.form.ComboBox({
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
	    }), '-', '&nbsp;&nbsp;', {xtype:'checkbox', boxLabel:'Show all security groups', listeners:
	    	{check:function(item, checked){
		    	store.baseParams.show_all = checked ? 'true' : 'false'; 
	        	store.load();
	    	}}
		}],
		
        // Columns
        columns:[
			{header: "Name", width: 70, dataIndex: 'name', sortable: true},
			{header: "Description", width: 50, dataIndex: 'description', sortable: false}
		],
		
    	// Row menu
    	rowOptionsMenu: [
      	             	
			{id: "option.edit", 		text:'Edit', 			  	href: "/sec_group_edit.php?name={name}"}
     	],
     	getRowOptionVisibility: function (item, record) {

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