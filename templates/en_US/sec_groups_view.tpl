{include file="inc/header.tpl"}
<br />
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">

var uid = '{$smarty.session.uid}';

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
	        remoteSort: true,
	
	        fields: [
				'id','name','description'
	        ]
    	}),
		url: '/server/grids/sec_groups_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
		
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Security groups",
        id: 'sgroups_list',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No security groups found"
        },

        enableFilter: false,
        
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
	        	store.baseParams.region = record.data.value; 
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
			{header: "Name", width: 70, dataIndex: 'name', sortable: false},
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