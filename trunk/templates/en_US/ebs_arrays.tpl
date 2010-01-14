{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer grid-padding-fix"></div>
<br />
<div id="maingrid-ct2" class="ux-gridviewer grid-padding-fix"></div>
<script type="text/javascript">

var uid = '{$smarty.session.uid}';

{literal}
Ext.onReady(function () {

	Ext.QuickTips.init();
	
	// create the Data Store for EBS
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'id',
	        	        
	        fields: [
				'name','size','status','avail_zone','volumes','instance_id','farmid','autosnapshoting','id','region'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/ebs_arrays_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });


    var snapsStore = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'id',
	        	
	        fields: [
				'id', 'status', 'description', 'region', 'dtcreated', 'clientid', 'ebs_arrayid'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/ebs_array_snaps_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	
	function autosnapRenderer(value, p, record) {
		if (value)
			return "<img src='/images/true.gif' />";
		else
			return "<img src='/images/false.gif' />";
	}

	function volumesRenderer(value, p, record)
	{
		return value+' [<a href="ebs_manage.php?arrayid='+record.data.id+'">View</a>]';
	}

	
	function assignRenderer(value, p, record) {
		var data = record.data;

		if (data.instance_id)
			return '<a href="instances_view.php?farmid='+data.farmid+'&iid='+data.instance_id+'">'+data.instance_id+'</a>';
		else
			return '<img src="/images/false.gif" />'; 
	}

	function snapIdRenderer(value, p, record) {
		return 'array-snap-'+value;
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 350,
        title: "EBS arrays",
        id: 'ebs_arrays_list_'+GRID_VERSION,
        store: store,
        maximize: false,
        viewConfig: { 
        	emptyText: "No arrays found"
        },

        enableFilter: true,
        
		tbar: [{
	        icon: '/images/add.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: 'Create a new EBS array',
	        handler: function()
	        {
				document.location.href = '/ebs_array_create.php';
	        }
	    }],
		
        // Columns
        columns:[
			{header: "Name", width: 120, dataIndex: 'name', sortable: true},
			{header: "Size (GB)", width: 35, dataIndex: 'size', sortable: true},
			{header: "Status", width: 50, dataIndex: 'status', sortable: true},
			{header: "Placement", width: 50, dataIndex: 'avail_zone', sortable: true},
			{header: "EBS Volumes", width: 50, dataIndex: 'volumes', renderer:volumesRenderer, sortable: false},
			{header: "Assigned to", width: 60, dataIndex: 'id', renderer:assignRenderer, sortable: false, align:'center'},
			{header: "Auto-snaphots", width: 50, dataIndex: 'autosnapshoting', renderer:autosnapRenderer, sortable: false, align:'center'}
		],
		
    	// Row menu
    	rowOptionsMenu: [    	          	             	
			{id: "option.attach", 	text:'Attach', 		href: "/ebs_array_attach.php?array_id={id}"},
			{id: "option.detach", 	text:'Detach', 		href: "/ebs_array_detach.php?array_id={id}"},
			new Ext.menu.Separator({id: "option.attachSep"}),
			{id: "option.createSnap", 	text:'Create snapshot', 		href: "/ebs_arrays.php?task=snap_create&array_id={id}"},
			{id: "option.viewSnaps", 	text:'View snapshots', 		handler: function(menuItem){

				snapsStore.baseParams.array_id = menuItem.parentMenu.record.data.id; 
				snapsStore.load();
				
			}}, 
			new Ext.menu.Separator({id: "option.viewSnapSep"}),
			{id: "option.autosnap", 	text:'Auto-snapshot settings', 		href: "/ebs_autosnaps.php?task=settings&array_id={id}&region={region}"},
			new Ext.menu.Separator({id: "option.autoSnapSep"}),
			{id: "option.recreate", 	text:'Recreate array', 		href: "/ebs_arrays.php?task=recreate&array_id={id}"},
			{id: "option.delete", 	text:'Delete array', 		href: "/ebs_arrays.php?task=delete&array_id={id}"}
     	],
     	getRowOptionVisibility: function (item, record) {

			if (item.id == 'option.delete')
				return true;

			if (item.id == 'option.recreate' && record.data.status != 'Corrupt')
				return false;
			else if (item.id == 'option.recreate' && record.data.status == 'Corrupt')
				return true;
			else if (item.id != 'option.recreate' && record.data.status != 'Corrupt')
			{
				if (item.id == 'option.attach' && record.data.instance_id)
					return false;

				if (item.id == 'option.detach' && !record.data.instance_id)
					return false;
				
				return true;
			}

			return false;
		},
		listeners: {
			beforeshowoptions: function (grid, record, romenu, ev) {
				romenu.record = record;
			}
		}
    });
    
    grid.render();
    store.load();

    var snaps_grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct2",
        height: 350,
        title: "EBS Array snapshots",
        id: 'ebs_array_snaps_list_'+GRID_VERSION,
        store: snapsStore,
        maximize: false,
        viewConfig: { 
        	emptyText: "No array snapshots found"
        },

        enableFilter: false,
        
		//tbar: [],
		
        // Columns
        columns:[
			{header: "Snapshot ID", width: 40, dataIndex: 'id', renderer:snapIdRenderer, sortable: false},
			{header: "Status", width: 25, dataIndex: 'status', sortable: false},
			{header: "Created at", width: 45, dataIndex: 'dtcreated', sortable: false},
			{header: "Comment", width: 120, dataIndex: 'description', sortable: false}
		],
		
    	// Row menu
    	rowOptionsMenu: [      	             	    	          	             	
			{id: "option.create2", 	text:'Create new array based on this snapshot', 		href: "/ebs_array_create.php?snapid={id}"},
			new Ext.menu.Separator({id: "option.Sep"}),
			{id: "option.delete2", 	text:'Delete snapshot', 		href: "/ebs_arrays.php?task=snap_delete&snapshotId={id}"}
     	],
     	getRowOptionVisibility: function (item, record) {

        	if (item.id != 'option.delete')
        	{
				if (record.data.status != 'Completed')
					return false;
				else
					return true;
        	}
        	
			return true;
		}
    });

    snaps_grid.render();
    snapsStore.load();
    
	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}