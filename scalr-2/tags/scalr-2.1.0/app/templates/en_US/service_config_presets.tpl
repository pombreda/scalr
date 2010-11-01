{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-service-config-presets-view"></div>

<script type="text/javascript">
{literal}
Ext.onReady(function() {
	var store = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
			root: 'data',
			successProperty: 'success',
			errorProperty: 'error',
			totalProperty: 'total',
			id: 'id',
			fields: [ 'id','env_id','client_id','name','role_behavior','dtadded','dtlastmodified' ]
		}),
		remoteSort: true,
		url: '/server/grids/service_config_presets.php?s=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-service-config-presets-view",
		autoRender: true,
		store: store,
		enablePaging: false,
		enableFilter: false,
		stateId: 'listview-service-config-presets-view',
		stateful: true,
		title: 'Service configuration presets',

		tbar: [{
			icon: '/images/add.png',
			cls: 'x-btn-icon',
			tooltip: 'Add new configuration preset',
			handler: function() {
				document.location.href = '/service_config_preset_add.php';
			}
		}],

		// Row menu
		rowOptionsMenu: [
			{ itemId: "option.edit", 	text: 'Edit', 		href: "/service_config_preset_add.php?preset_id={id}" } 
		],

		withSelected: {
			menu: [
				{
					text: 'Delete',
					params: {
						action: 'delete',
						with_selected: 1
					},
					confirmationMessage: 'Remove selected preset(s)?'
				}
			]
		},

		listViewOptions: {
			emptyText: "No presets defined",
			columns: [
				{ header: "ID", width: 15, dataIndex: 'id', sortable:true, hidden: 'no' },
				{ header: "Name", width: 40, dataIndex: 'name', sortable:true, hidden: 'no' },
				{ header: "Role behavior", width: 40, dataIndex: 'role_behavior', sortable: true, hidden: 'no' },
				{ header: "Added at", width: 50, dataIndex: 'dtadded', sortable: false, hidden: 'no' },
				{ header: "Last time modified", width: 50, dataIndex: 'dtlastmodified', sortable: false, hidden: 'no' }
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}