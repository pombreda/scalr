{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-environments-view"></div>

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
				'id', 'name', 'dtAdded', 'isSystem'
			]
		}),
		url: '/server/grids/environments_list.php'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-environments-view',
		autoRender: true,
		store: store,
		enableFilter: false,
		enablePaging: false,
		title: 'Environments',

		listViewOptions: {
			emptyText: 'No environments found',
			columns: [
				{ header: "Name", width: 30, dataIndex: 'name', sortable: true, hidden: 'no' },
				{ header: "Date added", width: 10, dataIndex: 'dtAdded', sortable: true, hidden: 'no' },
				{ header: "System", width: "70px", dataIndex: 'isSystem', sortable: false, hidden: 'no', align: 'center', tpl:
					'<tpl if="isSystem == 1"><img src="/images/true.gif"></tpl>' +
					'<tpl if="isSystem != 1">-</tpl>'
				}
			]
		},
		rowOptionsMenu: [
			{ itemId: "option.details", 		text:'Edit', 			  	href: "/environment_edit.php?env_id={id}" }
		]
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
