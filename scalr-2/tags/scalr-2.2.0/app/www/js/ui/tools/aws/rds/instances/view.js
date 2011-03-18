{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			reader: new Scalr.data.JsonReader({
				id: 'name',
				fields: [
					"engine", "status", "hostname", "port", "name", "username", "type", "storage",
					"dtadded", "avail_zone"
				]
			}),
			remoteSort: true,
			url: '/tools/aws/rds/instances/xListInstances/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Tools &raquo; Amazon Web Services &raquo; RDS &raquo; DB Instances',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			enableFilter: false,

			store: store,
			stateId: 'listview-tools-aws-rds-instances-view',

			tbar: [ 'Location:',
				new Ext.form.ComboBox({
					itemId: 'cloudLocation',
					editable: false,
					store: Scalr.data.createStore(moduleParams.locations, { idProperty: 'id', fields: [ 'id', 'name' ]}),
					typeAhead: false,
					displayField: 'name',
					valueField: 'id',
					value: '',
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false,
					width: 150,
					listeners: {
						select: function(combo, record, index) {
							store.baseParams.cloudLocation = combo.getValue();
							store.load();
						},
						added: function() {
							this.setValue(this.store.getAt(0).get('id'));
							store.baseParams.cloudLocation = this.store.getAt(0).get('id');
						}
					}
				}), {
					icon: '/images/add.png',
					cls: 'x-btn-icon',
					tooltip: 'Launch new DB instance',
					handler: function() {
						document.location.href = '/aws_rds_create_instance.php';
					}
				}
			],

			rowOptionsMenu: [
				{id: "option.details",			text: 'Details',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/rds/instances/' + item.currentRecordData.name + '/details?cloudLocation' + store.baseParams.cloudLocation;
					}
				},
				{id: "option.update",			text: 'Modify', 				href: "/aws_rds_instance_modify.php?name={name}"},
				new Ext.menu.Separator({id: "option.detailsSep"}),
				{id: "option.createSnap",		text: 'Create snapshot', 		href: "/aws_rds_snapshots.php?name={name}&action=create"},
				{id: "option.autoSnap",			text: 'Auto snapshot settings', href: "/autosnapshots.php?name={name}"},
				{id: "option.snaps",			text: 'Manage snapshots', 		href: "/aws_rds_snapshots.php?name={name}"},
				new Ext.menu.Separator({id: "option.cwSep"}),
				{id: "option.cw",				text: 'CloudWatch monitoring',	href: "/aws_cw_monitor.php?ObjectId={name}&Object=DBInstanceIdentifier&NameSpace=AWS/RDS"},
				new Ext.menu.Separator({id: "option.snapsSep"}),
				{id: "option.events",			text: 'Events log', 			href: "/aws_rds_events_log.php?type=db-instance&name={name}"},
				new Ext.menu.Separator({id: "option.eventsSep"}),
				{ itemId: "option.reboot",		text: 'Reboot', confirmationMessage: 'Reboot selected server?',
					menuHandler: function(item) {
						Ext.MessageBox.show({
							progress: true,
							msg: 'Sending reboot command to the server. Please wait...',
							wait: true,
							width: 450,
							icon: 'scalr-mb-instance-rebooting'
						});

						Ext.Ajax.request({
							url: '/tools/aws/rds/instances/xReboot/',
							success: function(response, options) {
								Ext.MessageBox.hide();

								var result = Ext.decode(response.responseText);
								if (result.success == true) {
									store.reload();
								} else {
									Scalr.Viewers.ErrorMessage(result.error);
								}
							},
							params: {
								instanceId: item.currentRecordData.name,
								cloudLocation: store.baseParams.cloudLocation
							}
						});
					}
				},
				{ itemId: "option.terminate",		text: 'Terminate', confirmationMessage: 'Terminate selected server?',
					menuHandler: function(item) {
						Ext.MessageBox.show({
							progress: true,
							msg: 'Sending terminate command to the server. Please wait...',
							wait: true,
							width: 450,
							icon: 'scalr-mb-instance-terminating'
						});

						Ext.Ajax.request({
							url: '/tools/aws/rds/instances/xTerminate/',
							success: function(response, options) {
								Ext.MessageBox.hide();

								var result = Ext.decode(response.responseText);
								if (result.success == true) {
									store.reload();
								} else {
									Scalr.Viewers.ErrorMessage(result.error);
								}
							},
							params: {
								instanceId: item.currentRecordData.name,
								cloudLocation: store.baseParams.cloudLocation
							}
						});
					}
				}
			],

			listViewOptions: {
				emptyText: 'No db instances found',
				columns: [
					{ header: "Name", width: 50, dataIndex: 'name', sortable: false, hidden: 'no' },
					{ header: "Hostname", width: 110, dataIndex: 'hostname', sortable: false, hidden: 'no' },
					{ header: "Port", width: 30, dataIndex: 'port', sortable: false, hidden: 'no' },
					{ header: "Status", width: 30, dataIndex: 'status', sortable: false, hidden: 'no' },
					{ header: "Username", width: 30, dataIndex: 'username', sortable: false, hidden: 'no' },
					{ header: "Type", width: 30, dataIndex: 'type', sortable: true, hidden:false, hidden: 'no' },
					{ header: "Storage", width: 20, dataIndex: 'storage', sortable: false, hidden: 'no' },
					{ header: "Placement", width: 30, dataIndex: 'avail_zone', sortable: false, hidden: 'no' },
					{ header: "Created at", width: 30, dataIndex: 'dtadded', sortable: false, hidden: 'no' }
				]
			}
		});
	}
}
