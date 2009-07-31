{include file="inc/header.tpl"}
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
	        	
	        fields: [
				'id', 'instance_type', 'avail_zone', 'duration', 
				'fixed_price', 'usage_price', 'description'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/reserved_offerings_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

    store.baseParams.region = region;
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Reserved instances offerings",
        id: 'reserved_instances_off_list',
        store: store,
        maximize: true,
        enableFilter: false,
        viewConfig: { 
        	emptyText: "No reserved instances offerings found"
        },

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
			{header: "ID", width: 150, dataIndex: 'id', sortable: false},
			{header: "Type", width: 70, dataIndex: 'instance_type', sortable: false},
			{header: "Placement", width: 70, dataIndex: 'avail_zone', sortable: false},
			{header: "Duration", width: 50, dataIndex: 'duration', renderer:function(value, p, record){ return (value == 1) ? value+" year" : value+" years"; }, sortable: false, align:'center'},
			{header: "Usage Price", width: 50, dataIndex: 'usage_price', renderer:function(value, p, record){ return '$'+value; }, sortable: false, align:'center'},
			{header: "Fixed Price", width: 50, dataIndex: 'fixed_price', renderer:function(value, p, record){ return '$'+value; }, sortable: false, align:'center'},
			{header: "Description", width: 70, dataIndex: 'description', sortable: false}
		],

		// Row menu
    	rowOptionsMenu: [
			{id: "option.purchase", 		text:'Purchase', handler: function(menuItem){

				var offer_id = menuItem.parentMenu.record.data.id; 

				Ext.MessageBox.confirm('Confirm', 'Are you sure want to purchase selected offering?', function(res){
					if (res == 'yes')
					{
						Ext.MessageBox.show({
				           msg: 'Sending request. Please wait...',
				           progressText: 'Processing...',
				           width:350,
				           wait:true,
				           waitConfig: {interval:200},
				           icon:'ext-mb-info', //custom class in msg-box.html
				           animEl: 'mb7'
				       	});

						$('Webta_ErrMsg').style.display = 'none';

						Ext.Ajax.request({
						   url: '/server/server.php',
						   success: function(){
								document.location.href = '/ec2_reserved_instances.php?code=1';
						   },
						   failure: function(response,options) {
							   	Ext.MessageBox.hide();
							   	var err_obj = $('Webta_ErrMsg');
								err_obj.innerHTML = 'Cannot proceed your request at the moment. Please try again later.';
								err_obj.style.display = '';	
								grid.autoSize();
								new Effect.Pulsate(err_obj);
						   },
						   params: { _cmd: 'purchaseReservedOffering', offeringID:offer_id , region: store.baseParams.region}
						});
					}
			    });								
			}}
     	],
		
     	getRowOptionVisibility: function (item, record) {
			return true;
		},
		listeners: {
			beforeshowoptions: function (grid, record, romenu, ev) {
				romenu.record = record;
			}
		}
    });
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}