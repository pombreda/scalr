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
				'name','dtcreated', 'dnsname', 'farmid', 'role_name', 'farm_name'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/elbs_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	
	function usedOnRenderer(value, p, record) {

		var data = record.data;
		var retval = "";

		if (record.data.role_name && record.data.farmid)
		{
			retval = 'Farm: <a href="farms_view.php?id='+data.farmid+'" title="Farm '+data.farm_name+'">'+data.farm_name+'</a>';
			retval += '&nbsp;&rarr;&nbsp;<a href="roles_view.php?farmid='+data.farmid+'" title="Role '+data.role_name+'">'+data.role_name+'</a>';
		}
		else
		{
			retval = "Not used by Scalr";
		}

		return retval;
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Elastic Load Balancers",
        id: 'elb_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No elastic load balancers found"
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
        
        enableFilter: false,
		
        // Columns
        columns:[
			{header: "Name", width: 100, dataIndex: 'name', sortable: false},
			{header: "Used on", width: 70, dataIndex: 'name', renderer:usedOnRenderer, sortable: false},
			{header: "DNS name", width: 150, dataIndex: 'dnsname', sortable: false},
			{header: "Created at", width: 60, dataIndex: 'dtcreated', sortable: false}
		],
		
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.details", 		text:'Details', 			  	href: "/aws_elb_details.php?name={name}"},
			new Ext.menu.Separator({id: "option.delSep"}),
			{id: "option.delete", 		text:'Remove', 			  		href: "/aws_elb.php?name={name}&action=remove"}
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