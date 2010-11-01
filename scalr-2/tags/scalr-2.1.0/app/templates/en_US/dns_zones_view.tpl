{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-dns-zones-view"></div>

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
				{name: 'client_id', type: 'int'},
				'zone_name', 'status', 'role_name', 'farm_roleid', 'dtlastmodified', 'farm_id', 'farm_name'
			]
		}),
		remoteSort: true,
		url: '/server/grids/dns_zones_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-dns-zones-view",
		autoRender: true,
		store: store,
		savePagingSize: true,
		saveFilter: true,
		stateId: 'listview-dns-zones-view',
		stateful: true,
		title: 'DNS zones',

		// Row menu
		rowOptionsMenu: [
			{itemId: "option.edit", 		text:'Edit DNS Zone', 		href: "/dns_zone_edit.php?zone_id={id}"},
			{itemId: "option.ZoneSettings", text:'Settings', 			href: "/dns_zone_settings.php?zone_id={id}"},
			new Ext.menu.Separator({itemId: "option.editSep"}),
			{itemId: "option.switch", 	text: 'Switch application to another farm / role', 	href: "/dns_zone_switch_role.php?zone_id={id}"}
		],

		getRowOptionVisibility: function (item, record) {
			if (item.itemId == 'option.switch' && record.data.status == 'Inactive')
				return false;
			else
				return true;
		},

		getRowMenuVisibility: function (data) {
			return (data.status != 'Pending delete' && data.status != 'Pending create');
		},

		withSelected: {
			menu: [
				{
					text: 'Delete',
					method: 'ajax',
					params: {
						action: 'RemoveDNSZones'
					},
					confirmationMessage: 'Remove selected dns zone(s)?',
					progressMessage: 'Removing dns zone(s). Please wait...',
					progressIcon: 'scalr-mb-object-removing',
					url: '/server/ajax-ui-server.php',
					dataHandler: function(records) {
						var zones = [];
						for (var i = 0, len = records.length; i < len; i++) {
							zones[zones.length] = records[i].id;
						}
						return { zones: Ext.encode(zones) };
					}
				}
			]
		},

		listViewOptions: {
			emptyText: "No DNS zones found",
			columns: [
				{ header: "Domain name", width: 10, dataIndex: 'zone', sortable: true, hidden: 'no', tpl: '<a target="_blank" href="http://{values.zone_name}">{values.zone_name}</a>' },
				{ header: "Assigned to", width: 10, dataIndex: 'role_name', sortable: false, hidden: 'no', tpl:
					'<tpl if="farm_id &gt; 0"><a href="farms_view.php?id={values.farm_id}" title="Farm {values.farm_name}">{values.farm_name}</a>' +
						'<tpl if="farm_roleid &gt; 0">&nbsp;&rarr;&nbsp;<a href="farm_roles_view.php?farm_roleid={values.farm_roleid}&farmid={values.farm_id}" ' + 
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
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}