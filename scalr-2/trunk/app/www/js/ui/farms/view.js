{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [
					{name: 'id', type: 'int'},
					{name: 'clientid', type: 'int'},
					{name: 'dtadded', type: 'date'},
					'name', 'status', 'servers', 'roles', 'zones','client_email','havemysqlrole','shortcuts'
				]
			}),
			remoteSort: true,
			url: '/farms/xListViewFarms/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Farms &raquo; View',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { farmId: '', clientId: '', status: ''});
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-farms-view',

			listViewOptions: {
				emptyText: "No farms found",
				columns: [
					{ header: "Farm ID", width: 5, dataIndex: 'id', sortable: true, hidden: 'no' },
					{ header: "Farm Name", width: 10, dataIndex: 'name', sortable: true, hidden: 'no' },
					{ header: "Added", width: 10, dataIndex: 'dtadded', tpl: '{[values.dtadded ? values.dtadded.dateFormat("M j, Y") : ""]}', sortable: true, hidden: 'no' },
					{ header: "Roles", width:  10, dataIndex: 'roles', tpl: '{roles} [<a href="#/farms/{id}/roles">View</a>]', sortable: false, hidden: 'no' },
					{ header: "Servers", width: 10, dataIndex: 'servers', tpl: '{servers} [<a href="#/servers/view?farmId={id}">View</a>]', sortable: false, hidden: 'no' },
					{ header: "DNS zones", width: 10, dataIndex: 'zones', tpl: '{zones} [<a href="#/dnszones/view?farmId={id}">View</a>]', sortable: false, hidden: 'no' },
					{ header: "Status", width: 5, dataIndex: 'status', tpl:
						new Ext.XTemplate('<span class="{[this.getClass(values.status)]}">{[this.getName(values.status)]}</span>', {
							getClass: function (value) {
								if (value == 1)
									return "status-ok";
								else if (value == 3)
									return "status-ok-pending";
								else
									return "status-fail";
							},
							getName: function (value) {
								var titles = {
									1: "Running",
									0: "Terminated",
									2: "Terminating",
									3: "Synchronizing"
								};
								return titles[value] || value;
							}
						}), sortable: true, hidden: 'no'
					}
			]},
			rowOptionsMenu: [{
				itemId: "option.launchFarm",
				text: 'Launch',
				iconCls: 'scalr-menu-icon-launch',
				request: {
					confirmBox: {
						type: 'launch',
						msg: 'Are you sure want to launch farm "{name}" ?'
					},
					processBox: {
						type: 'launch',
						msg: 'Launching farm. Please wait...'
					},
					url: '/farms/xLaunch/',
					dataHandler: function (record) {
						return { farmId: record.get('id') };
					},
					success: function () {
						store.reload();
						Scalr.Message.Success('Farm successfully launched');
					}
				}
			},
				{itemId: "option.terminateFarm", iconCls: 'scalr-menu-icon-terminate', text: 'Terminate', href: "/farms_control.php?farmid={id}"},
				new Ext.menu.Separator({itemId: "option.controlSep"}),
				{itemId: "option.usageStats", text: 'Usage statistics', href: "/farm_usage_stats.php?farmid={id}"},
				{itemId: "option.loadStats", iconCls: 'scalr-menu-icon-stats', text: 'Load statistics', href: "/monitoring.php?farmid={id}"},
	
				{itemId: "option.ebs",			text: 'EBS usage', 				href: "/ebs_manage.php?farmid={id}"},
				{itemId: "option.eip",			text: 'Elastic IPs usage', 		href: "/elastic_ips.php?farmid={id}"},
				{itemId: "option.events",		text: 'Events & Notifications', href: "#/farms/{id}/events"},
	
				new Ext.menu.Separator({itemId: "option.mysqlSep"}),
	
				{itemId: "option.mysql",		text: 'MySQL status', 			href: "/farm_mysql_info.php?farmid={id}"},
				{itemId: "option.script", iconCls: 'scalr-menu-icon-execute', text: 'Execute script', href: "#/scripts/execute?farmId={id}"},
	
				new Ext.menu.Separator({itemId: "option.logsSep"}),
				{itemId: "option.logs", iconCls: 'scalr-menu-icon-logs', text: 'View log', href: "#/logs/system?farmId={id}"},
				new Ext.menu.Separator({itemId: "option.editSep"}),
				{itemId: "option.edit", iconCls: 'scalr-menu-icon-configure', text: 'Configure', href: "/farms_builder.php?id={id}"},
			{
				itemId: 'option.delete',
				iconCls: 'scalr-menu-icon-delete',
				text: 'Delete',
				request: {
					confirmBox: {
						type: 'delete',
						msg: 'Are you sure want to remove farm "{name}" ?'
					},
					processBox: {
						type: 'delete',
						msg: 'Removing farm. Please wait...'
					},
					url: '/farms/xRemove/',
					dataHandler: function (record) {
						return { farmId: record.get('id') };
					},
					success: function () {
						store.reload();
						Scalr.Message.Success('Farm successfully removed');
					}
				}
			}, {
				xtype: 'menuseparator',
				itemId: 'option.scSep'
			}],

			getRowOptionVisibility: function (item, record) {
				var data = record.data;
	
				if (item.itemId == "option.launchFarm")
					return (data.status == 0);
	
				if (item.itemId == "option.terminateFarm")
					return (data.status == 1);
	
				if (item.itemId == "option.scSep")
					return (data.shortcuts.length > 0);
	
				if (item.itemId == "option.viewMap" ||
						item.itemId == "option.viewMapSep" ||
						item.itemId == "option.loadStats" ||
						item.itemId == "option.mysqlSep" ||
						item.itemId == "option.mysql" ||
						item.itemId == "option.script"
					) {
	
					if (data.status == 0)
						return false;
					else
					{
						if (item.itemId != "option.mysql")
							return true;
						else
							return data.havemysqlrole;
					}
				}
				else
					return true;
			},
			listeners: {
				'beforeshowoptions': {fn: function (grid, record, romenu, ev) {
					romenu.record = record;
					var data = record.data;

					romenu.items.each(function (item) {
						if (item.isshortcut) {
							item.parentMenu.remove(item);
						}
					});

					if (data.shortcuts.length > 0)
					{
						for (i in data.shortcuts)
						{
							if (typeof(data.shortcuts[i]) != 'function')
							{
								romenu.add({
									//id:'option.'+(Math.random()*100000),
									isshortcut:1,
									xmenu:romenu,
									text:'Execute '+data.shortcuts[i].name,
									href:'#/scripts/execute?eventName='+data.shortcuts[i].event_name
								});
							}
						}
					}
				}}
			}
		});
	}
}
