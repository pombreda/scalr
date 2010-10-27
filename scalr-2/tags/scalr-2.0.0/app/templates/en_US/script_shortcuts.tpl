{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-script-shortcuts-view"></div>

<script type="text/javascript">
{literal}
Ext.onReady(function () {
	var store = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
			id: 'id',
			fields: [
				{name: 'id', type: 'int'},
				'farmid', 'farmname', 'ami_id', 'rolename', 'scriptname', 'event_name'
	        ]
    	}),
		url: '/server/grids/script_shortcuts_list.php?a=1{/literal}{$grid_query_string}{literal}'
    });

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-script-shortcuts-view",
		autoRender: true,
		store: store,
		saveFilter: true,
		stateId: 'listview-script-shortcuts-view',
		stateful: true,
		title: 'Script shortcuts',

		rowOptionsMenu: [
			{ itemId: "option.edit", text: 'Edit', href: "/execute_script.php?script={event_name}&task=edit&farmid={farmid}"}
		],

		withSelected: {
			menu: [
				{
					text: "Delete",
					method: 'post',
					params: {
						action: 'delete',
						with_selected: 1
					},
					confirmationMessage: 'Delete selected shortcut(s)?',
					url: '/script_shortcuts.php'
				}
			],
		},

		listViewOptions: {
			emptyText: "No shortcuts defined",
			columns: [
				{ header: "Target", width: 150, dataIndex: 'id', sortable: false, hidden: 'no', tpl:
					'<a href="farms_view.php?id={farmid}">{farmname}</a>' +
					'<tpl if="ami_id">&rarr;<a href="farm_roles_view.php?farmid={farmid}&ami_id={ami_id}">{rolename}</a></tpl>' +
					'&nbsp;&nbsp;&nbsp;'
				},
				{ header: "Script", width: 500, dataIndex: 'scriptname', sortable: true, hidden: 'no' }
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
