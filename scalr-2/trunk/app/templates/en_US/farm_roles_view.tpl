{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-farm-roles-view"></div>

<script type="text/javascript">
	var farm_status = '{$farm_status}';

{literal}
Ext.onReady(function () {
	var store = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
			root: 'data',
			successProperty: 'success',
			errorProperty: 'error',
			totalProperty: 'total',
			id: 'id',
			fields: [
				{name: 'id', type: 'int'}, 'platform',
				'name', 'min_count', 'max_count', 'min_LA', 'max_LA', 'servers', 'domains', 
				'image_id', 'farmid','shortcuts', 'role_id', 'scaling_algos', 'farm_status', 'region'
			]
		}),
		remoteSort: true,
		url: '/server/grids/farm_roles_list.php?a=1{/literal}{$grid_query_string}&farmid={$farmid}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-farm-roles-view',
		autoRender: true,
		store: store,
		savePagingSize: true,
		savePagingNumber: true,
		saveFilter: true,
		stateId: 'listview-farm-roles-view',
		stateful: true,
		title: 'Farm roles',

    	rowOptionsMenu: [
			{itemId: "option.cfg", 			text:'Configure', 			  			href: "/farms_add.php?id={farmid}&role_id={role_id}&configure=1"},
			{itemId: "option.stat", 		text:'View statistics', 			  	href: "/monitoring.php?role={id}&farmid={farmid}"},
			{itemId: "option.info", 		text:'Extended role information', 		href: "/farm_role_view_extended_info.php?farm_roleid={id}"},
			new Ext.menu.Separator({itemId: "option.mainSep"}),
			{itemId: "option.exec", 		text: 'Execute script', 				href: "/execute_script.php?farm_roleid={id}"},
			new Ext.menu.Separator({itemId: "option.eSep"}),
			{itemId: "option.sgEdit", 		text: 'Edit security group', 			href: "/sec_group_edit.php?role_name={name}&region={region}"},
			new Ext.menu.Separator({itemId: "option.sgSep"}),
			{itemId: "option.launch", 		text: 'Launch new instance', 			href: "/farm_roles_view.php?farmid={farmid}&action=launch_new_instance&farm_roleid={id}"},
			new Ext.menu.Separator({itemId: "option.scSep"})
     	],

     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (item.itemId == "option.scSep")
				return (data.shortcuts.length > 0);
			
			if (item.itemId == 'option.stat' || item.itemId == 'option.cfg')
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
			emptyText: "No clients defined",
			columns: [
				{ header: "Platform", width: 15, dataIndex: 'platform', sortable: true, hidden: 'no' },
				{ header: "Role name", width: 40, dataIndex: 'name', sortable: false, hidden: 'no', tpl:
					'<a href="/roles_view.php?id={role_id}">{name}</a>'
				},
				{header: "Image ID", width: 30, dataIndex: 'image_id', sortable: true, hidden: 'no', tpl:
					'<a href="/roles_view.php?id={role_id}">{image_id}</a>'
				},
				{ header: "Min servers", width: 15, dataIndex: 'min_count', sortable: false, align:'center', hidden: 'no' },
				{ header: "Max servers", width: 15, dataIndex: 'max_count', sortable: false, align:'center', hidden: 'no' },
				{ header: "Enabled scaling algorithms", width: 70, dataIndex: 'scaling_algos', sortable: false, align:'center', hidden: 'no' },
				{ header: "Servers", width: 20, dataIndex: 'servers', sortable: false, hidden: 'no', tpl:
					'{servers} [<a href="/servers_view.php?farmid={farmid}&farm_roleid={id}">View</a>]'
				},
				{ header: "Domains", width: 20, dataIndex: 'domains', sortable: false, hidden: 'no', tpl:
					'{domains} [<a href="/dns_zones_view.php?farm_roleid={id}">View</a>]'
				}
			]
		},

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
								href:'execute_script.php?farmid='+data.shortcuts[i].farmid+'&task=execute&script='+data.shortcuts[i].event_name
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
});
{/literal}
</script>
{include file="inc/footer.tpl"}