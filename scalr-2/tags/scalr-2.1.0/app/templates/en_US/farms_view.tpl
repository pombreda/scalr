{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-farms-view"></div>
<script type="text/javascript">
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
				{name: 'id', type: 'int'},
				{name: 'clientid', type: 'int'},
				{name: 'dtadded', type: 'date'},
				'name', 'status', 'servers', 'roles', 'zones','client_email','havemysqlrole','shortcuts'
			]
		}),
		remoteSort: true,
		url: '/server/grids/farms_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	new Scalr.Viewers.ListView({
		renderTo: "listview-farms-view",
		autoRender: true,
		store: store,
		savePagingSize: true,
		savePagingNumber: true,
		saveFilter: true,
		stateId: 'listview-farms-view',
		stateful: true,
		title: 'Farms',

		rowOptionsMenu: [
			//{id: "option.viewMap", 		text:'View map', 			  	href: "/farm_map.php?id={id}"},
			//new Ext.menu.Separator({id: "option.viewMapSep"}),

			{itemId: "option.privateKey", 	text: 'Download Private key', 	href: "/storage/keys/{id}/{name}.pem"},
			new Ext.menu.Separator({itemId: "option.privateKeySep"}),
			{itemId: "option.launchFarm", 	text: 'Launch', 				href: "/farms_control.php?farmid={id}"},
			{itemId: "option.terminateFarm",text: 'Terminate', 				href: "/farms_control.php?farmid={id}"},
			new Ext.menu.Separator({itemId: "option.controlSep"}),
			{itemId: "option.usageStats",	text: 'Usage statistics', 		href: "/farm_usage_stats.php?farmid={id}"},
			{itemId: "option.loadStats",	text: 'Load statistics', 		href: "/monitoring.php?farmid={id}"},

			{itemId: "option.ebs",			text: 'EBS usage', 				href: "/ebs_manage.php?farmid={id}"},
			{itemId: "option.eip",			text: 'Elastic IPs usage', 		href: "/elastic_ips.php?farmid={id}"},
			{itemId: "option.events",		text: 'Events & Notifications', href: "/events_view.php?farmid={id}"},

			new Ext.menu.Separator({itemId: "option.mysqlSep"}),

			{itemId: "option.mysql",		text: 'MySQL status', 			href: "/farm_mysql_info.php?farmid={id}"},
			{itemId: "option.script",		text: 'Execute script', 		href: "/execute_script.php?farmid={id}"},

			new Ext.menu.Separator({itemId: "option.logsSep"}),
			{itemId: "option.logs",			text: 'View log', 				href: "/logs_view.php?farmid={id}"},
			new Ext.menu.Separator({itemId: "option.editSep"}),
			{itemId: "option.edit",			text: 'Edit', 					href: "/farms_builder.php?id={id}"},
			{itemId: "option.delete",		text: 'Delete', 				href: "/farm_delete.php?id={id}"},

			new Ext.menu.Separator({itemId: "option.scSep"})
		],
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

		listViewOptions: {
			emptyText: "No farms found",
			columns: [
				{ header: "Farm ID", width: 5, dataIndex: 'id', sortable: true, hidden: 'no' },
				{/literal}
					{if $Scalr_Session->getClientId() == 0}
						{literal}
						{ header: "Client", width: 10, dataIndex: 'client', tpl: '<a href="/clients_view.php?clientid={clientid}">{client_email}</a>', sortable: false, hidden: 'no' },
						{/literal}
					{/if}
				{literal}
				{ header: "Farm Name", width: 10, dataIndex: 'name', sortable: true, hidden: 'no' },
				{ header: "Added", width: 10, dataIndex: 'dtadded', tpl: '{[values.dtadded ? values.dtadded.dateFormat("M j, Y") : ""]}', sortable: true, hidden: 'no' },
				{ header: "Roles", width:  10, dataIndex: 'roles', tpl: '{roles} [<a href="/farm_roles_view.php?farmid={id}">View</a>]', sortable: false, hidden: 'no' },
				{ header: "Servers", width: 10, dataIndex: 'servers', tpl: '{servers} [<a href="/servers_view.php?farmid={id}">View</a>]', sortable: false, hidden: 'no' },
				{ header: "DNS zones", width: 10, dataIndex: 'zones', tpl: '{zones} [<a href="/dns_zones_view.php?farmid={id}">View</a>]', sortable: false, hidden: 'no' },
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
			]
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
								href:'execute_script.php?farmid='+data.shortcuts[i].farmid+'&task=execute&script='+data.shortcuts[i].event_name
							});
						}
					}
				}
			}}
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}