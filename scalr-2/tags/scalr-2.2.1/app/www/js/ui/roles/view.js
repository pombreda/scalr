{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [
					{name: 'id', type: 'int'},
					{name: 'client_id', type: 'int'},
					'name', 'tags', 'origin', 'architecture', 'client_name', 'behaviors', 'os', 'platforms','generation','used_servers','status'
				]
			}),
			remoteSort: true,
			url: '/roles/xListViewRoles/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Roles &raquo; View',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { roleId: '', client_id: '' });
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-roles-view',

			tbar: [
				' ',
				'Location:',
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
						}
					}
				}),
				'-', ' ',
				'Origin:',
				new Ext.form.ComboBox({
					itemId: 'origin',
					allowBlank: true,
					editable: false,
					store: [ [ '', 'All' ], [ 'Shared', 'Scalr' ], [ 'Custom', 'Private' ] ],
					value: '',
					typeAhead: false,
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false,
					emptyText: ' ',
					width: 150,
					listeners: {
						select: function(combo, record, index) {
							store.baseParams.origin = combo.getValue();
							store.load();
						}
					}
				})
			],

			listViewOptions: {
				emptyText: "No roles found",
				columns: [
					{ header: "Role name", width: 50, dataIndex: 'name', sortable: true, hidden: 'no'},
					{ header: "OS", width: 25, dataIndex: 'os', sortable: true, hidden: 'no'},
					{ header: "Owner", width: 30, dataIndex: 'client_id', sortable: false, hidden: 'no', tpl: new Ext.XTemplate(
						'<tpl if="this.isScalrAdmin && client_id != &quot;&quot;"><a href="clients_view.php?client_id={client_id}"></tpl>{client_name}<tpl if="this.isScalrAdmin && client_id != &quot;&quot;"></a></tpl>',
						{ isScalrAdmin: moduleParams.isScalrAdmin }
					)},
					{ header: "Behaviors", width: '150px', dataIndex: 'behaviors', sortable: false, hidden: 'no'},
					{ header: "Available on", width: '240px', dataIndex: 'platforms', sortable: false, hidden: 'no'},
					{ header: "Tags", width: '140px', dataIndex: 'tags', sortable: false, hidden: 'no'},
					{ header: "Arch", width: '65px', dataIndex: 'architecture', sortable: true, hidden: 'no'},
					{ header: "Status", width: '100px', dataIndex: 'status', sortable: false, hidden: 'no'},
					{ header: "Scalr agent", width: '100px', dataIndex: 'generation', sortable: false, hidden: 'no'},
					{ header: "Servers", width: '60px', dataIndex: 'used_servers', sortable: false, hidden: 'yes'}
				]
			},
			// Row menu
			rowOptionsMenu: [
				{ itemId: "option.view", iconCls: 'scalr-menu-icon-info', text:'View details', href: "#/roles/{id}/info" },
				{ itemId: "option.edit", iconCls: 'scalr-menu-icon-edit', text:'Edit', href: "#/roles/{id}/edit" }
			],

			getRowOptionVisibility: function (item, record) {
				if (item.itemId == 'option.view')
					return true;

				if (record.data.origin == 'CUSTOM') {
					if (item.itemId == 'option.edit') {
						if (! moduleParams.isScalrAdmin)
							return true;
						else
							return false;
					}

					return true;
				}
				else {
					return moduleParams.isScalrAdmin;
				}
			},
			
			getRowMenuVisibility: function (data) {
				return (data.status.indexOf('Deleting') == -1);
			},

			withSelected: {
				menu: [{
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							msg: 'Remove selected role(s)?',
							type: 'delete'
						},
						processBox: {
							msg: 'Removing selected role(s)... Please wait, it can take a few minutes.',
							type: 'delete'
						},
						url: '/roles/xRemove',
						dataHandler: function (records) {
							var roles = [];
							for (var i = 0, len = records.length; i < len; i++) {
								roles[roles.length] = records[i].get('id');
							}

							return { roles: Ext.encode(roles) };
						},
						success: function (data) {
							Scalr.Message.Success('Selected roles successfully removed');
						}
					}
				}]
			}
		});
	}
}
