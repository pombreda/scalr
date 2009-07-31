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
				'ipaddress','instance_id', 'farmid', 'farm_name', 'role_name', 'indb'
	        ]
    	}),
		url: '/server/grids/eips_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	

	function farmRenderer (value, p, record) {
		if (record.data.farmid && record.data.farm_name)
			return '<a href="farms_view.php?id='+record.data.farmid+'">'+record.data.farm_name+'</a>';
		else
			return "Not used by Scalr";		
	}

	function roleRenderer (value, p, record) {
		if (record.data.role_name)
			return '<a href="roles_view.php?farmid='+record.data.farmid+'">'+record.data.role_name+'</a>';
		else
			return 'Not used by Scalr';								
	}

	function indbRenderer (value, p, record) {
		if (record.data.indb)
			return '<img src="images/true.gif">';
		else
			return '<img src="images/false.gif">';								
	}

	function instanceRenderer(value, p, record) {
		if (record.data.role_name)
			return '<a href="instances_view.php?iid='+record.data.instance_id+'&farmid='+record.data.farmid+'">'+record.data.instance_id+'</a>';
		else
			return record.data.instance_id;
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Elastic IPs",
        id: 'eips_list',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No elastic IPs found"
        },

        enableFilter: false,
        
		tbar: [{text: 'Region:'}, new Ext.form.ComboBox({
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
	    })],
		
        // Columns
        columns:[
			{header: "Farm name", width: 70, dataIndex: 'farm_name', renderer:farmRenderer, sortable: true},
			{header: "Role name", width: 50, dataIndex: 'role_name', renderer:roleRenderer, sortable: false},
			{header: "IP address", width: 60, dataIndex: 'ipaddress', sortable: false},
			{header: "Used by role", width: 40, dataIndex: 'role_name', renderer:indbRenderer, sortable: true, align:'center'},
			{header: "Instance", width: 30, dataIndex: 'insatnce_id', renderer:instanceRenderer, sortable: true} 
		],
		
    	// Row menu
    	rowOptionsMenu: [
      	             	
			{id: "option.release", 		text:'Release', 			  	href: "/elastic_ips.php?task=release&ip={ipaddress}"}
     	],
     	getRowOptionVisibility: function (item, record) {

			return true;
		}
    });
    
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}