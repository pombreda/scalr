{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [
					{name: 'id', type: 'int'},
					{name: 'client_id', type: 'int'},
					'zone_name', 'status', 'role_name', 'farm_roleid', 'dtlastmodified', 'farm_id', 'farm_name'
				]
			}),
			remoteSort: true,
			url: '/dnszones/xListViewZones/'
		});

		return new Scalr.Viewers.ListView({
			title: 'DNS Zones &raquo; View',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { dnsZoneId: '', clientId: '', farmId: '', farmRoleId: ''});
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-dnszones-view',

			listViewOptions: {
				emptyText: "No DNS zones found",
				columns: [
				{ header: "Domain name", width: 10, dataIndex: 'zone', sortable: true, hidden: 'no', tpl: '<a target="_blank" href="http://{values.zone_name}">{values.zone_name}</a>' },
				{ header: "Assigned to", width: 10, dataIndex: 'role_name', sortable: false, hidden: 'no', tpl:
					'<tpl if="farm_id &gt; 0"><a href="#/farms/{values.farm_id}/view" title="Farm {values.farm_name}">{values.farm_name}</a>' +
						'<tpl if="farm_roleid &gt; 0">&nbsp;&rarr;&nbsp;<a href="#/farms/{values.farm_id}/roles/{values.farm_roleid}/view" ' +
						'title="Role {values.role_name}">{values.role_name}</a></tpl>' +
					'</tpl>' +
					'<tpl if="farm_id == 0"><img src="/images/false.gif" /></tpl>'
				},
				{ header: "Last modified", width: 10, dataIndex: 'dtlastmodified', sortable: true, hidden: 'no',
					tpl: '<tpl if="dtlastmodified">{values.dtlastmodified}</tpl><tpl if="! dtlastmodified">Never</tpl>'
				},
				{ header: "Status", width: 5, dataIndex: 'status', sortable: false, hidden: 'no', tpl:
					new Ext.XTemplate('<span class="{[this.getClass(values.status)]}">{values.status}</span>', {
						getClass: function (value) {
							if (value == 'Active')
								return "status-ok";
							else if (value == 'Pending create' || value == 'Pending update')
								return "status-ok-pending";
							else
								return "status-fail";
						}
					})
				}
			]},
			rowOptionsMenu: [
				{ text:'Edit DNS Zone', href: "#/dnszones/{id}/edit" },
				{ text:'Settings', href: "#/dnszones/{id}/settings" }
			],

			getRowMenuVisibility: function (data) {
				return (data.status != 'Pending delete' && data.status != 'Pending create');
			},

			withSelected: {
				menu: [{
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							type: 'delete',
							msg: 'Remove selected dns zone(s)?'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing dns zone(s). Please wait...'
						},
						url: '/dnszones/xRemoveZones',
						dataHandler: function(records) {
							var zones = [];
							for (var i = 0, len = records.length; i < len; i++) {
								zones[zones.length] = records[i].id;
							}
							return { zones: Ext.encode(zones) };
						}
					}
				}]
			}
		});
	}
}
