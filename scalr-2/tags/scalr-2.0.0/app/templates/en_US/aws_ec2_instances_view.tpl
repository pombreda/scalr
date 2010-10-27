	{include file="inc/header.tpl"}
	<link rel="stylesheet" href="css/grids.css" type="text/css" />
	<div id="maingrid-ct" class="ux-gridviewer"></div>
	<script type="text/javascript">
	
	var FarmID = '{$smarty.get.farmid}';
	/*
	var regions = [
	{section name=id loop=$regions}
		['{$regions[id]}','{$regions[id]}']{if !$smarty.section.id.last},{/if}
	{/section}
	];
	*/

	var region = '{$smarty.session.aws_region}';

	var regions = [
	{foreach from=$regions name=id key=key item=item}
		['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
	{/foreach}
	];
	
	var region = '{$smarty.session.aws_region}';
	
	{literal}
	Ext.onReady(function () {
	
	Ext.QuickTips.init();
		// create the Data Store
	    var store = new Ext.ux.scalr.Store({
	    	reader: new Ext.ux.scalr.JsonReader({
		        root: 'data',
		        successProperty: 'success',
		        errorProperty: 'error',
		        totalProperty: 'total',
		        id: 'iid',	   
		        fields: [
					'iid', 'imageId', 'instanceState','dnsName','keyName','instanceType','launchTime',
					'availabilityZone','monState','instanceLifecycle'
		        ]
	    	}),
	    	remoteSort: true,
			url: '/server/grids/aws_ec2_instances_list.php?a=1{/literal}{$grid_query_string}{literal}',
			listeners: { dataexception: Ext.ux.dataExceptionReporter }
	    });
	
			
	    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
		var grid = new Ext.ux.scalr.GridViewer(
		{
	        renderTo: "maingrid-ct",
	        height: 500,
	        title: "Running spot instances",
	        id: 'ec2_instances_list_'+GRID_VERSION,
	        store: store,
	        maximize: true,
	        viewConfig: 
			{ 
	        	emptyText: "No running spot instances were found"
			},
				
	        enableFilter: false,	
	        
	        
	        // Columns
	        columns:
	        [			
				{header: "Instance ID",			width: 40, dataIndex: 'iid',				sortable: false},
				{header: "Image ID",			width: 40, dataIndex: 'imageId',			sortable: false},
				{header: "Instance state",		width: 40, dataIndex: 'instanceState',		sortable: false},
				{header: "instance type",		width: 40, dataIndex: 'instanceType',		sortable: false},
				{header: "DNS name",			width: 40, dataIndex: 'dnsName',			sortable: false},			
				{header: "availability zone",	width: 40, dataIndex: 'availabilityZone',	sortable: false},
				{header: "Lifecycle",			width: 40, dataIndex: 'instanceLifecycle',	sortable: false}	
				
			],
			
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
		        listeners:
			        {	select:function(combo, record, index){
		        		store.baseParams.region = combo.getValue(); 
		        		store.load();
		        	}}
		    	}),

		    {
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Launch new DB instance',
				handler: function()
				{
					document.location.href = '/aws_ec2_amis_view.php';
				}
			 }
		    ],	
		    
			// Row menu
	    	rowOptionsMenu: 
	        [    
				{id: "option.details",	text: 'Details', 	href: "/aws_ec2_instance_info.php?iid={iid}"}
	     	],
	     	getRowOptionVisibility: function (item, record) {
	
				return true;
			},
			withSelected: 
			{
				menu: 
				[
					{text: "Delete", value: "delete" }
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