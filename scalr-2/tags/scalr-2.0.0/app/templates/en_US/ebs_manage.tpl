{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-ebs-manage-view"></div>
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
	var store = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
			root: 'data',
			successProperty: 'success',
			errorProperty: 'error',
			totalProperty: 'total',
			id: 'volume_id',

			fields: [
				'farm_id', 'farm_roleid', 'arrayid', 'farm_name', 'role_name', 'mysql_master_volume', 'mount_status', 'server_index',
				'volume_id', 'size', 'snapshot_id', 'avail_zone', 'status', 'attachment_status', 'device', 'instance_id', 'auto_snaps'
			]
		}),
		remoteSort: true,
		url: '/server/grids/ebs_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var snapsStore = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
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
		url: '/server/grids/ebs_snaps_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var ebsPanel = new Scalr.Viewers.ListView({
		store: store,
		savePagingSize: true,
		saveFilter: true,
		stateId: 'listview-ebs-volumes-view',
		stateful: true,
		title: 'EBS volumes',
		maximize: false,
		enableFilter: false,
		flex: 1,

		tbar: [
			'Location:',
			new Ext.form.ComboBox({
				allowBlank: false,
				editable: false, 
				store: regions,
				value: region,
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus: false,
				width: 100,
				listeners: {
					select: function(combo, record, index) {
						store.baseParams.region = region = combo.getValue();
						store.load();
					}
				}
			}),
			'-',
			{
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Create a new EBS volume',
				handler: function() {
					document.location.href = '/ebs_manage.php?task=create_volume';
				}
			}
		],

		// Row menu
		rowOptionsMenu: [
			{itemId: "option.attach", 		text:'Attach', 			  	href: "/ebs_manage.php?task=attach&volumeId={volume_id}"},
			{itemId: "option.detach", 		text:'Detach', 			  	href: "/ebs_manage.php?task=detach&volumeId={volume_id}"},
			new Ext.menu.Separator({itemId: "option.attachSep"}),
			{itemId: "option.autosnap", 	text:'Auto-snapshot settings', menuHandler: function(menuItem) {
				document.location.href = '/autosnapshots.php?task=settings&volumeId=' + menuItem.currentRecordData.volume_id + '&region=' + region;
			}},
			new Ext.menu.Separator({itemId: "option.snapSep"}),
			{itemId: "option.createSnap", 	text:'Create snapshot', 	href: "/ebs_manage.php?task=snap_create&volumeId={volume_id}"},
			{itemId: "option.viewSnaps", 	text:'View snapshots', 		menuHandler: function(menuItem) {
				snapsStore.baseParams.volumeid = menuItem.currentRecordData.volume_id; 
				snapsStore.load();
			}}, 
			new Ext.menu.Separator({itemId: "option.vsnapSep"}),
			{itemId: "option.delete", 	text:'Delete volume', confirmationMessage: 'Are you sure want remove this volume?',
				menuHandler: function(item) {
					Ext.MessageBox.show({
						progress: true,
						msg: 'Removing volume. Please wait...',
						wait: true,
						width: 450,
						icon: 'scalr-mb-object-removing'
					});

					Ext.Ajax.request({
						url: '/server/ajax-ui-server.php',
						success: function(response, options) {
							Ext.MessageBox.hide();

							var result = Ext.decode(response.responseText);
							if (result.result == 'ok') {
								store.load();
							} else {
								Scalr.Viewers.ErrorMessage(result.msg);
							}
						},
						params: {
							action: 'RemoveVolume',
							volume_id: item.currentRecordData.volume_id,
							region: region
						}
					});
				}
			}
		],

		getRowOptionVisibility: function (item, record) {
			if (item.itemId == 'option.attach' || item.itemId == 'option.detach' || item.itemId == 'option.attachSep')
			{
				if (!record.data.mysql_master_volume)
				{
					if (item.itemId == 'option.attachSep')
						return true;

					if (item.itemId == 'option.detach' && record.data.instance_id)
						return true;

					if (item.itemId == 'option.attach' && !record.data.instance_id)
						return true;
				}

				return false;
			}
			
			return true;
		},

		listViewOptions: {
			emptyText: "No volumes found",
			columns: [
				{ header: "Used by", width: 70, dataIndex: 'id', sortable: false, hidden: 'no', tpl:
					'<tpl if="farm_id">' +
						'Farm: <a href="farms_view.php?id={farm_id}" title="Farm {farm_name}">{farm_name}</a>' +
						'<tpl if="role_name">' +
							'&nbsp;&rarr;&nbsp;<a href="farm_roles_view.php?farmid={farm_id}&farm_roleid={farm_roleid}" title="Role {role_name}">' +
							'{role_name}</a> #{server_index}' +
						'</tpl>' +
						'<tpl if="!role_name && mysql_master_volume">&nbsp;&rarr;&nbsp;MySQL master volume</tpl>' +
					'</tpl>' +
					'<tpl if="!farm_id"><img src="/images/false.gif" /></tpl>'
				},
				{ header: "Volume ID", width: 25, dataIndex: 'volume_id', sortable: false, hidden: 'no' },
				{ header: "Size (GB)", width: 15, dataIndex: 'size', sortable: false, hidden: 'no' },
				{ header: "Snapshot ID", width: 35, dataIndex: 'snapshot_id', sortable: false, hidden: 'yes' },
				{ header: "Placement", width: 20, dataIndex: 'avail_zone', sortable: false, hidden: 'no' },
				{ header: "Status", width: 35, dataIndex: 'status', sortable: false, hidden: 'no', tpl:
					'{status}' +
					'<tpl if="attachment_status"> / {attachment_status}</tpl>' +
					'<tpl if="device"> ({device})</tpl>'
				},
				{ header: "Mount status", width: 20, dataIndex: 'mount_status', sortable: false, hidden: 'no', tpl:
					'<tpl if="mount_status">{mount_status}</tpl>' +
					'<tpl if="!mount_status"><img src="/images/false.gif" /></tpl>'
				},
				{ header: "Instance ID", width: 30, dataIndex: 'instance_id', sortable: false, hidden: 'no', tpl:
					'<tpl if="instance_id">{instance_id}</tpl>'
				},
				{ header: "Auto-snaps", width: 20, dataIndex: 'auto_snaps', sortable: false, align:'center', hidden: 'no', tpl:
					'<tpl if="auto_snaps"><img src="/images/true.gif" /></tpl>' +
					'<tpl if="!auto_snaps"><img src="/images/false.gif" /></tpl>'
				}
			]
		}
    });

	var snapsPanel = new Scalr.Viewers.ListView({
		store: snapsStore,
		savePagingSize: true,
		saveFilter: true,
		stateId: 'listview-ebs-snaps-view',
		stateful: true,
		title: 'EBS snapshots',
		maximize: false,
		flex: 1,

		tbar: [
			'&nbsp;&nbsp;',
			{
				xtype:'checkbox',
				boxLabel: 'Show public (Shared) snapshots',
				listeners: {
					check: function(item, checked) {
						snapsStore.baseParams.show_public_snapshots = checked ? 'true' : 'false'; 
						snapsStore.load();
					}
				}
			}
		],

		// Row menu
		rowOptionsMenu: [
			{itemId: "option.create", 	text:'Create new volume based on this snapshot', 		href: "/ebs_manage.php?task=create_volume&snapid={snap_id}"},
			new Ext.menu.Separator({itemId: "option.Sep"}),
			{itemId: "option.delete_snap", 	text:'Delete snapshot', href: "/ebs_manage.php?task=snap_delete&snapshotId={snap_id}", confirmationMessage: 'Remove snapshot?'}
		],

		withSelected: {
			menu: [
				{
					text: 'Remove',
					method: 'ajax',
					params: {
						action: 'RemoveSnapshots'
					},
					confirmationMessage: 'Remove selected snapshot(s)?',
					progressMessage: 'Removing snapshot(s). Please wait...',
					progressIcon: 'scalr-mb-object-removing',
					url: '/server/ajax-ui-server.php',
					dataHandler: function(records) {
						var snaps = [];
						for (var i = 0, len = records.length; i < len; i++) {
							snaps[snaps.length] = records[i].id;
						}
						return { snapshots: Ext.encode(snaps) };
					}
				}
			]
		},

		listViewOptions: {
			emptyText: "No snapshots found",
			columns: [
				{ header: "Snapshot ID", width: 40, dataIndex: 'snap_id', sortable: false, hidden: 'no' },
				{ header: "Owner", width: 120, dataIndex: 'owner', sortable: false, hidden: 'no' },
				{ header: "Created on", width: 35, dataIndex: 'volume_id', sortable: false, hidden: 'no' },
				{ header: "Status", width: 25, dataIndex: 'status', sortable: false, hidden: 'no' },
				{ header: "Local start time", width: 45, dataIndex: 'time', sortable: false, hidden: 'no' },
				{ header: "Completed", width: 25, dataIndex: 'progress', sortable: false, align:'center', hidden: 'no', tpl: '{progress}%' },
				{ header: "Comment", width: 120, dataIndex: 'comment', sortable: false, hidden: 'no', tpl:
					'<tpl if="comment">{comment}</tpl>'
				}
			]
		}
	});

	var panel = new Ext.Panel({
		renderTo: 'listview-ebs-manage-view',
		autoRender: true,
		border: false,
		layout: 'vbox',
		layoutConfig: {
			align: 'stretch'
		},
		items: [
			ebsPanel,
			{
				height: 5,
				border: false,
				xtype: 'panel',
				html: '&nbsp;'
			},
			snapsPanel
		]
	});
	
	var autoSize = new Scalr.Viewers.autoSize();
	autoSize.init(panel);
});
{/literal}
</script>
{include file="inc/footer.tpl"}