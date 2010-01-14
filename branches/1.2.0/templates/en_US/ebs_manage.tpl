{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div class="grid-padding-fix">
	<div id="maingrid-ct" class="ux-gridviewer"></div>
	<br />
	<div id="maingrid-ct2" class="ux-gridviewer"></div>
</div>
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

	Ext.QuickTips.init();
	
	// create the Data Store for EBS
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'volume_id',
	        	
	        fields: [
				'farmid','arrayid', 'farm_name', 'role_name', 'mysql_master_volume', 'array_name','array_part_no',
				'volume_id', 'size', 'snapshot_id', 'avail_zone', 'status', 'attachment_status', 'device', 'instance_id', 'auto_snap'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/ebs_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });

    var snapsStore = new Ext.ux.scalr.Store({
        reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'snap_id',
	        	
	        fields: [
				'snap_id', 'volume_id', 'status', 'time', 'comment', 'is_array_snapshot', 'progress', 'owner'
	        ]
        }),
        remoteSort: true,
		url: '/server/grids/ebs_snaps_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	
	function statusRenderer(value, p, record) {
		var data = record.data;

		var retval = data.status;

		if (data.attachment_status)
			retval += ' / '+data.attachment_status

		return retval;
	}

	function autosnapRenderer(value, p, record) {

		if (value)
			return "<img src='/images/true.gif' />";
		else
			return "<img src='/images/false.gif' />";
	}

	function snapProgressRenderer(value, p, record)
	{
		return value+"%";
	}
	
	function usedRenderer(value, p, record) {
		var data = record.data;

		var retval = "";
		if (data.farmid && !data.arrayid)
		{
			retval += 'Farm: <a href="farms_view.php?id='+data.farmid+'" title="Farm '+data.farm_name+'">'+data.farm_name+'</a>';
			if (data.role_name)
			{
				retval += '&nbsp;&rarr;&nbsp;<a href="roles_view.php?farmid='+data.farmid+'" title="Role '+data.role_name+'">'+data.role_name+'</a>';
			}
			else if (data.mysql_master_volume)
			{
				retval += '&nbsp;&rarr;&nbsp;MySQL master volume';
			}
		}
		else if (data.arrayid)
		{
			retval += 'Array: <a href="ebs_arrays.php?id='+data.arrayid+'">'+data.array_name+'</a>&nbsp;&rarr;&nbsp;Part #'+data.array_part_no;
		}
		else
			retval += '<img src="/images/false.gif" />';

		return retval;
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 350,
        width:'100%',
        title: "EBS volumes",
        id: 'ebs_volumes_list_'+GRID_VERSION,
        store: store,
        maximize: false,
        viewConfig: { 
        	emptyText: "No volumes found"
        },

        enableFilter: false,
        
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
	        	region = combo.getValue();
	        	store.load();
	        }}
	    }), '-', {
	        icon: '/images/add.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: 'Create a new EBS volume',
	        handler: function()
	        {
				document.location.href = '/ebs_manage.php?task=create_volume';
	        }
	    }],
		
        // Columns
        columns:[
			{header: "Used by", width: 70, dataIndex: 'id', renderer:usedRenderer, sortable: false},
			{header: "Volume ID", width: 35, dataIndex: 'volume_id', sortable: false},
			{header: "Size (GB)", width: 20, dataIndex: 'size', sortable: false},
			{header: "Snapshot ID", width: 35, dataIndex: 'snapshot_id', sortable: false, hidden:true},
			{header: "Placement", width: 30, dataIndex: 'avail_zone', sortable: false},
			{header: "Status", width: 30, dataIndex: 'status', renderer:statusRenderer, sortable: false},
			{header: "Instance ID", width: 30, dataIndex: 'instance_id', sortable: false},
			{header: "Device", width: 30, dataIndex: 'device', sortable: false},
			{header: "Auto-snapshots", width: 30, dataIndex: 'auto_snap', renderer:autosnapRenderer, sortable: false, align:'center'}
		],
		
    	// Row menu
    	rowOptionsMenu: [    	          	             	
			{id: "option.attach", 		text:'Attach', 			  	href: "/ebs_manage.php?task=attach&volumeId={volume_id}"},
			{id: "option.detach", 		text:'Detach', 			  	href: "/ebs_manage.php?task=detach&volumeId={volume_id}"},
			new Ext.menu.Separator({id: "option.attachSep"}),
			{id: "option.autosnap", 	text:'Auto-snapshot settings', handler: function(menuItem){
				document.location.href = '/ebs_autosnaps.php?task=settings&volumeId='+menuItem.parentMenu.record.data.volume_id+'&region='+region;
			}},
			new Ext.menu.Separator({id: "option.snapSep"}),
			{id: "option.createSnap", 	text:'Create snapshot', 	href: "/ebs_manage.php?task=snap_create&volumeId={volume_id}"},
			{id: "option.viewSnaps", 	text:'View snapshots', 		handler: function(menuItem){

				snapsStore.baseParams.volumeid = menuItem.parentMenu.record.data.volume_id; 
				snapsStore.load();
				
			}}, 
			new Ext.menu.Separator({id: "option.vsnapSep"}),
			{id: "option.delete", 	text:'Delete volume', handler:function(menuItem){
				var Item = menuItem.parentMenu.record.data;
				window.TID = false;
				window.TIF = false;
				SendRequestWithConfirmation(
					{
						action: 'RemoveVolume', 
						volume_id: Item.volume_id,
						region: region
					},
					'Are you sure want remove this volume?',
					'Removing volume. Please wait...',
					'ext-mb-object-removing',
					function(){
						grid.autoSize();
					},
					function(){
						store.load();
					}
				);
			}}
     	],
     	getRowOptionVisibility: function (item, record) {

			if (item.id == 'option.attach' || item.id == 'option.detach' || item.id == 'option.attachSep')
			{
				if (!record.data.mysql_master_volume)
				{
					if (item.id == 'option.attachSep')
						return true;

					if (item.id == 'option.detach' && record.data.instance_id)
						return true;

					if (item.id == 'option.attach' && !record.data.instance_id)
						return true;
				}

				return false;
			}
        	
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

    var snaps_grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct2",
        height: 320,
        width:'100%',
        title: "EBS snapshots",
        id: 'ebs_snaps_list_'+GRID_VERSION,
        store: snapsStore,
        maximize: false,
        viewConfig: { 
        	emptyText: "No snapshots found"
        },

        enableFilter: true,
        
        tbar: ['&nbsp;&nbsp;', {xtype:'checkbox', boxLabel:'Show public (Shared) snapshots', listeners:
	    	{check:function(item, checked){
        		snapsStore.baseParams.show_public_snapshots = checked ? 'true' : 'false'; 
        		snapsStore.load();
	    	}}
		}],
		
        // Columns
        columns:[
			{header: "Snapshot ID", width: 40, dataIndex: 'snap_id', sortable: false},
			{header: "Owner", width: 120, dataIndex: 'owner', sortable: false},
			{header: "Created on", width: 35, dataIndex: 'volume_id', sortable: false},
			{header: "Status", width: 25, dataIndex: 'status', sortable: false},
			{header: "Local start time", width: 45, dataIndex: 'time', sortable: false},
			{header: "Completed", width: 25, dataIndex: 'progress', renderer:snapProgressRenderer, sortable: false, align:'center'},
			{header: "Comment", width: 120, dataIndex: 'comment', sortable: false}
		],
		
    	// Row menu
    	rowOptionsMenu: [    	          	             	
			{id: "option.create", 	text:'Create new volume based on this snapshot', 		href: "/ebs_manage.php?task=create_volume&snapid={snap_id}"},
			new Ext.menu.Separator({id: "option.Sep"}),
			{id: "option.delete_snap", 	text:'Delete snapshot', 		href: "/ebs_manage.php?task=snap_delete&snapshotId={snap_id}"}
     	],
     	getRowOptionVisibility: function (item, record) {
        	
			return true;
		},
		withSelected: {
			menu: [
				{text: "Remove", value: "remove", handler:function(){

					var snapshots = snaps_grid.getSelectionModel().selections.keys;

					SendRequestWithConfirmation(
						{
							action: 'RemoveSnapshots', 
							snapshots: Ext.encode(snapshots)
						},
						'Remove selected snapshot(s)?',
						'Removing snapshot(s). Please wait...',
						'ext-mb-object-removing',
						function(){
							//snapsStore.autoSize();
						},
						function(){
							snapsStore.load();
						}
					);
					
				}}
			],
			hiddens: {with_selected : 1},
			action: "act"
		},
		listeners: {
			beforeshowoptions: function (grid, record, romenu, ev) {
				romenu.record = record;
			}
		}
    });

    snaps_grid.render();
    snapsStore.load();
    
	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}