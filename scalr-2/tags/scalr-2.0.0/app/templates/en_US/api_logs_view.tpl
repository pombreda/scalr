{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-api-logs-view"></div>

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
				'id','transaction_id','dtadded','action','ipaddress','request'
			]
		}),
		remoteSort: true,
		url: '/server/grids/api_log_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-api-logs-view',
		autoRender: true,
		store: store,
		title: 'API logs {/literal}({$table_title_text}){literal}',

    	rowOptionsMenu: [
			{ itemId: "option.details", 		text:'Details', 			  	href: "/api_log_entry_details.php?trans_id={transaction_id}" }
     	],

		listViewOptions: {
			emptyText: 'No logs found',
			columns: [
				{ header: "Transaction ID", width: 35, dataIndex: 'transaction_id', sortable: false, hidden: 'no' },
				{ header: "Time", width: 35, dataIndex: 'dtadded', sortable: false, hidden: 'no' },
				{ header: "Action", width: 15, dataIndex: 'action', sortable: false, hidden: 'no' },
				{ header: "IP address", width: 25, dataIndex: 'ipaddress', sortable: false, hidden: 'no' }
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}