{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer"></div>
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
	        	
	        fields: [
				'id', 'task_name', 'task_type', 'target_name', 'target_type', 'start_time_date',
				'end_time_date', 'last_start_time', 'restart_every','order_index', 'farmid','farm_name','status'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/scheduler_tasks_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
		
	function targetRenderer(value, p, record) 
	{		
		var data = record.data;
		var retval = "";		
		switch(data.target_type)
		{
			case "farm":					
				retval += 'Farm: <a href="farms_view.php?id='+data.farmid+'" title="Farm '+data.target_name+'">'+data.target_name+'</a>';					
				break;
				
			case "role":
				retval += 'Farm: <a href="farms_view.php?id='+data.farmid+'" title="Farm '+data.farm_name+'">'+data.farm_name+'</a>';									
				retval += '&nbsp;&rarr;&nbsp;Role: <a href="roles_view.php?farmid='+data.farmid+'" title="Role '+data.target_name+'">'+data.target_name+'</a>';
				break;
				
			case "instance":
				retval += 'Farm: <a href="farms_view.php?id='+data.farmid+'" title="Farm '+data.farm_name+'">'+data.farm_name+'</a>';									
				retval += '&nbsp;&rarr;&nbsp;Instance: <a href="instances_view.php?farmid='+data.farmid+'" title="Instance '+data.target_name+'">'+data.target_name+'</a>';
				break;
		}	
		return  retval;	
	}
	
	function colorRenderer(value, p, record)
	{
		var data = record.data;
		switch(data.status)
		{
			case "Active": 
				data.status = "<span style='color:green;'>"+data.status+"</span>";
				
				break;
			case "Suspended":
				data.status = "<span style='color:blue;'>"+data.status+"</span>";
				
				break;
			case "Finished":
				data.status = "<span style='color:red;'>"+data.status+"</span>";
				
				break;
			
		}
	return data.status;
		
		
	}

    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
    
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Script tasks",
        id: 'schedule_tasks_list_'+GRID_VERSION,
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No tasks defined"
        },
		 
		enableFilter: true,
		tbar: 
        [ 
			 {
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Add new request',
				handler: function()
				{
					document.location.href = '/scheduler_task_add.php?task=create';
				}
			 }
	    ],
	    
        // Columns
        columns:
        [
			{header: "ID", width: 15, dataIndex: 'id', sortable: true},
			{header: "Task name", width: 40, dataIndex: 'task_name', sortable: true},
			{header: "Task type", width: 40, dataIndex: 'task_type', sortable: false},			
			{header: "Target name", width: 40, dataIndex: 'target_name',renderer:targetRenderer, sortable: true},
	    //	{header: "Target type", width: 40, dataIndex: 'target_type',renderer:targetRenderer, sortable: true},
			{header: "Start date", width: 50, dataIndex: 'start_time_date', sortable: true},
			{header: "End date", width: 50, dataIndex: 'end_time_date', sortable: true},
			{header: "Last time executed", width: 50, dataIndex: 'last_start_time', sortable: true},
			{header: "Priority", width: 20, dataIndex: 'order_index', sortable: true},
			{header: "Status", width: 20, dataIndex: 'status',renderer:colorRenderer, sortable: true}			
		],

    	// Row menu
    	rowOptionsMenu: 
    	[
    		{id: "option.activate", text: 'Activate',	href: "/scheduler.php?&task=activate&id={id}" },
			{id: "option.suspend", text: 'Suspend',   	href: "/scheduler.php?&task=suspend&id={id}"},
			new Ext.menu.Separator({id: "option.editSep"}),
			{id: "option.edit", text: 'Edit', 			href: "/scheduler_task_add.php?task=edit&id={id}"}			
     	],

     	getRowOptionVisibility: function (item, record)
     	{
     		var data = record.data;
			
			if (item.id == "option.activate" || item.id == "option.suspend" || item.id == "option.editSep")
			{
				var reg =/Finished/i
				if(reg.test(data.status))			
					return false;
			}
			var reg =/Active/i
     		if (item.id == "option.activate" && reg.test(data.status))
				return false;
				
			var reg =/Suspended/i
			if (item.id == "option.suspend"  && reg.test(data.status))
				return false;
			
			return true;
		},

		getRowMenuVisibility: function (record) {
			return true;
		},
		withSelected: {
			menu: [
				{text: "Delete", value: "delete"},
				{text: "Activate", value: "activate"},				
				{text: "Suspend", value: "suspend"}
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