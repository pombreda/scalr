{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-bundle-tasks-view"></div>

<script type="text/javascript">

var uid = '{$smarty.session.uid}';

var regions = [
{foreach from=$regions name=id key=key item=item}
	['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
{/foreach}
];

var region = '{$smarty.session.aws_region}';

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
	            'server_id','prototype_role_id','replace_type','status','platform','rolename','failure_reason','bundle_type','dtadded',
	            'dtstarted','dtfinished','snapshot_id','platform_status','server_exists'
			]
		}),
		remoteSort: true,
		url: '/server/grids/bundle_tasks.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-bundle-tasks-view',
		autoRender: true,
		store: store,
		title: 'Bundle tasks',

    	// Row menu
    	rowOptionsMenu: [
			{itemId: "option.logs", 		text:'View log', 			  	href: "/bundle_task_log.php?task_id={id}"},
			{itemId: "option.cancel", 		text:'Cancel', 			  		href: "/bundle_tasks.php?task_id={id}&action=cancel"}
     	],
     	getRowOptionVisibility: function (item, record) {
			if (item.itemId == 'option.cancel')
			{
				if (record.data.status == 'in-progress')
					return true;
				else
					return false;
			}

			return true;
		},

		listViewOptions: {
			emptyText: 'No bundle tasks found',
			columns: [
				{ header: "ID", width: 20, dataIndex: 'id', sortable: true, hidden: 'no' },
				{ header: "Server ID", width: 70, dataIndex: 'server_id', sortable: true, hidden: 'no', tpl:
					'<tpl if="server_exists"><a href="server_view_extended_info.php?server_id={server_id}">{server_id}</a></tpl>' +
					'<tpl if="!server_exists">{server_id}</tpl>'
				},
				{ header: "Role name", width: 50, dataIndex: 'rolename', sortable: true, hidden: 'no' },
				{ header: "Status", width: 50, dataIndex: 'status', sortable: true, hidden: 'no', tpl:
					'<tpl if="status == &quot;failed&quot;">{status} (<a href="bundle_task_failure_details.php?task_id={id}">Why?</a>)</tpl>' +
					'<tpl if="status != &quot;failed&quot;">{status}</tpl>'
				},
				{ header: "Type", width: 50, dataIndex: 'platform', sortable: true, hidden: 'no', tpl: '{platform}/{bundle_type}' },
				{ header: "Added", width: 50, dataIndex: 'dtadded', sortable: true, hidden: 'no' },
				{ header: "Started", width: 50, dataIndex: 'dtstarted', sortable: true, hidden: 'no', tpl:
					'<tpl if="dtstarted">{dtstarted}</tpl>'
				},
				{ header: "Finished", width: 50, dataIndex: 'dtfinished', sortable: true, hiden: 'no', tpl:
					'<tpl if="dtfinished">{dtfinished}</tpl>'
				}
			]
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
