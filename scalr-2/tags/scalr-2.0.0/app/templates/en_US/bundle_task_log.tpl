{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-bundle-tasks-log-view"></div>

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
				'dtadded',
				'message'
			]
		}),
		remoteSort: true,
		url: '/server/grids/bundle_task_log.php?a=1{/literal}{$grid_query_string}&task_id={$task_id}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-bundle-tasks-log-view',
		autoRender: true,
		store: store,
		enableFilter: false,
		enablePaging: false,
		title: 'Logs',

		listViewOptions: {
			emptyText: 'No log entries found',
			columns: [
				{ header: "Date", width: 100, dataIndex: 'dtadded', sortable: true, hidden: 'no' },
				{ header: "Message", width: 300, dataIndex: 'message', sortable: true, hidden: 'no' }
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}