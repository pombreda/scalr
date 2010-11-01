{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-scaling-metrics-view"></div>

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
			fields: [ 'id','env_id','client_id','name','file_path','retrieve_method','calc_function' ]
		}),
		remoteSort: true,
		url: '/server/grids/scaling_metrics.php?s=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-scaling-metrics-view",
		autoRender: true,
		store: store,
		enablePaging: false,
		enableFilter: false,
		stateId: 'listview-service-config-presets-view',
		stateful: true,
		title: 'Scaling metrics',

		tbar: [{
			icon: '/images/add.png',
			cls: 'x-btn-icon',
			tooltip: 'Create new scaling metric',
			handler: function() {
				document.location.href = '/scaling_metric_add.php';
			}
		}],

		// Row menu
		rowOptionsMenu: [
			{ itemId: "option.edit", 	text: 'Edit', 		href: "/scaling_metric_add.php?metric_id={id}" } 
		],

		withSelected: {
			menu: [
				{
					text: 'Delete',
					params: {
						action: 'delete',
						with_selected: 1
					},
					confirmationMessage: 'Remove selected metric(s)?'
				}
			]
		},

		listViewOptions: {
			emptyText: "No presets defined",
			columns: [
				{ header: "ID", width: 15, dataIndex: 'id', sortable:true, hidden: 'no' },
				{ header: "Name", width: 40, dataIndex: 'name', sortable:true, hidden: 'no' },
				{ header: "File path", width: 40, dataIndex: 'file_path', sortable: false, hidden: 'no' },
				{ header: "Retrieve method", width: 50, dataIndex: 'retrieve_method', sortable: false, hidden: 'no', tpl:
					'<tpl if="retrieve_method == \'read\'">File-Read</tpl>' +
					'<tpl if="retrieve_method == \'execute\'">File-Execute</tpl>'
				},
				{ header: "Calculation function", width: 50, dataIndex: 'calc_function', sortable: false, hidden: 'no', tpl:
					'<tpl if="calc_function == \'avg\'">Average</tpl>' +
					'<tpl if="calc_function == \'sum\'">Sum</tpl>'
				 }
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}