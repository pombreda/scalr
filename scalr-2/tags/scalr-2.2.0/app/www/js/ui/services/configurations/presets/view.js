{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [ 'id','env_id','client_id','name','role_behavior','dtadded','dtlastmodified' ]
			}),
			remoteSort: true,
			url: '/services/configurations/presets/xListViewPresets/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Services &raquo; Configurations &raquo; Presets',
			scalrOptions: {
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { presetId: ''});
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-services-presets-view',
			
			listViewOptions: {
				viewConfig: { 
		        	emptyText: "No presets found"
		        },
		        // Columns
		        columns:[
					{ header: "ID", width: 15, dataIndex: 'id', sortable:true, hidden: 'no' },
					{ header: "Name", width: 40, dataIndex: 'name', sortable:true, hidden: 'no' },
					{ header: "Role behavior", width: 40, dataIndex: 'role_behavior', sortable: true, hidden: 'no' },
					{ header: "Added at", width: 50, dataIndex: 'dtadded', sortable: false, hidden: 'no' },
					{ header: "Last time modified", width: 50, dataIndex: 'dtlastmodified', sortable: false, hidden: 'no' }
				]
			},
			tbar: [{
				icon: '/images/add.png',
				cls: 'x-btn-icon',
				tooltip: 'Add new configuration preset',
				handler: function() {
					document.location.href = '#/services/configurations/presets/build';
				}
			}],

			// Row menu
			rowOptionsMenu: [
				{ itemId: "option.edit", 	text: 'Edit', 		href: "#/services/configurations/presets/{id}/edit" } 
			],

			withSelected: {
				menu: [{
					text: 'Delete',
					method: 'ajax',
					confirmationMessage: 'Remove selected configuration preset(s) ?',
					progressMessage: 'Removing configuration preset(s). Please wait...',
					progressIcon: 'scalr-mb-object-removing',
					url: '/services/configurations/presets/xRemove/',
					dataHandler: function(records) {
						var presets = [];
						for (var i = 0, len = records.length; i < len; i++) {
							presets[presets.length] = records[i].id;
						}
						return { presets: Ext.encode(presets) };
					}
				}]
			}
		});
	}
}
