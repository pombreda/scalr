{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [
					{name: 'id', type: 'int'}, 'platform', 'location',
					'name', 'min_count', 'max_count', 'min_LA', 'max_LA', 'servers', 'domains', 
					'image_id', 'farmid','shortcuts', 'role_id', 'scaling_algos', 'farm_status', 'location'
				]
			}),
			remoteSort: true,
			url: '/farms/roles/xListViewFarmRoles/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Farms &raquo; ' + moduleParams['farmName'] + ' &raquo; Roles',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { roleId:'', farmRoleId: '', farmId: '', clientId: '', status: ''});
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-farmroles-view',

			rowOptionsMenu: [
     			{itemId: "option.ssh_key", 		text: 'Download SSH private key', 		menuHandler: function (item){
     				Scalr.Viewers.userLoadFile('/farms/' + loadParams['farmId'] + '/roles/' + item.currentRecordData.id + '/xGetRoleSshPrivateKey');
     			}},
     			{itemId: "option.cfg", 			text:'Configure', 			  			href: "/farms_builder.php?id={farmid}&role_id={role_id}"},
     			{itemId: "option.stat", 		text:'View statistics', 			  	href: "/monitoring.php?role={id}&farmid={farmid}"},
     			{itemId: "option.info", 		text:'Extended role information', 		href: "#/farms/" + loadParams['farmId'] + "/roles/{id}/extendedInfo"},
     			new Ext.menu.Separator({itemId: "option.mainSep"}),
     			{itemId: "option.exec", 		text: 'Execute script', 				href: "#/scripts/execute?farmRoleId={id}"},
     			new Ext.menu.Separator({itemId: "option.eSep"}),
     			{itemId: "option.sgEdit", 		text: 'Edit security group', 			href: "/sec_group_edit.php?farm_roleid={id}&location={location}&platform={platform}"},
     			new Ext.menu.Separator({itemId: "option.sgSep"}),
     			{itemId: "option.launch", 		text: 'Launch new instance', 			menuHandler: function (item) {
					Ext.Msg.wait("Please wait ...");

					Ext.Ajax.request({
						url: '/farms/' + loadParams['farmId'] + '/roles/' + item.currentRecordData.id + '/xLaunchNewServer',
						success: function (response) {
							var result = Ext.decode(response.responseText);
							if (result.success == true) {
								Scalr.Viewers.SuccessMessage('Server successfully launched');
								if (result.warnMsg)
									Scalr.Viewers.WarningMessage(result.warnMsg);
								store.reload();
							} else if (result.error)
								Scalr.Viewers.ErrorMessage(result.error);

							Ext.Msg.hide();
						},
						failure: function() {
							Ext.Msg.hide();
						}
					});
				}},
     			new Ext.menu.Separator({itemId: "option.scSep"})
          	],

          	getRowOptionVisibility: function (item, record) {
     			var data = record.data;

     			if (item.itemId == "option.scSep")
     				return (data.shortcuts.length > 0);
     			
     			if (item.itemId == "option.sgEdit")
     				return (data.platform == 'euca' || data.platform == 'ec2');
     			
     			if (item.itemId == 'option.stat' || item.itemId == 'option.cfg' || item.itemId == 'option.ssh_key' || item.itemId == 'option.info')
     			{
     				return true;
     			}
     			else
     			{
     				if (data.farm_status == 1)
     					return true;
     				else
     					return false;
     			}
     			
     			return true;
     		},
			
			listViewOptions: {
				emptyText: "No roles assigned to selected farm",
				columns: [
					{ header: "Platform", width: 15, dataIndex: 'platform', sortable: true, hidden: 'no' },
					{ header: "Location", width: 15, dataIndex: 'location', sortable: false, hidden: 'no' },
					{ header: "Role name", width: 40, dataIndex: 'name', sortable: false, hidden: 'no', tpl:
						'<a href="#/roles/{role_id}/view">{name}</a>'
					},
					{header: "Image ID", width: 30, dataIndex: 'image_id', sortable: true, hidden: 'no', tpl:
						'<a href="#/roles/{role_id}/view">{image_id}</a>'
					},
					{ header: "Min servers", width: 15, dataIndex: 'min_count', sortable: false, align:'center', hidden: 'no' },
					{ header: "Max servers", width: 15, dataIndex: 'max_count', sortable: false, align:'center', hidden: 'no' },
					{ header: "Enabled scaling algorithms", width: 70, dataIndex: 'scaling_algos', sortable: false, align:'center', hidden: 'no' },
					{ header: "Servers", width: 20, dataIndex: 'servers', sortable: false, hidden: 'no', tpl:
						'{servers} [<a href="#/servers/view?farmId={farmid}&farmRoleId={id}">View</a>]'
					},
					{ header: "Domains", width: 20, dataIndex: 'domains', sortable: false, hidden: 'no', tpl:
						'{domains} [<a href="#/dnszones/view?farmRoleId={id}">View</a>]'
					}
			]},
			listeners:{
				'beforeshowoptions': {fn: function (grid, record, romenu, ev) {
					var data = record.data;

					var rows = romenu.items.items;
					for (k in rows)
					{
						if (rows[k].isshortcut == 1)
							romenu.remove(rows[k]);
					}

					if (data.shortcuts.length > 0)
					{
						for (i in data.shortcuts)
						{
							if (typeof(data.shortcuts[i]) != 'function')
							{
								romenu.add({
									id:'option.'+(Math.random()*100000),
									isshortcut:1,
									text:'Execute '+data.shortcuts[i].name,
									href:'#/scripts/execute?eventName='+data.shortcuts[i].event_name
								});
							}
						}
					}
					else
					{
						var rows = romenu.items.items;
						for (k in rows)
						{
							if (rows[k].isshortcut == 1)
								romenu.remove(rows[k]);
						}
					}
				}}
			}
		});
	}
}
