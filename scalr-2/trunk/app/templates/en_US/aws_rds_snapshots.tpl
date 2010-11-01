{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-rds-snapshots-view"></div>
<script type="text/javascript">
var regions = [
{foreach from=$regions name=id key=key item=item}
	['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
{/foreach}
];

var region = '{$smarty.session.aws_region}';

{literal}
Ext.onReady(function () {
	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-rds-snapshots-view',
		autoRender: true,
		store: new Scalr.data.Store({
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [ 'id','name','storage','idtcreated','avail_zone','engine','status','port','dtcreated' ]
			}),
			remoteSort: true,
			url: '/server/grids/aws_rds_snapshots_list.php?a=1{/literal}{$grid_query_string}{literal}'
		}),
		savePagingSize: true,
		saveFilter: true,
		stateId: 'listview-rds-snapshots-view',
		stateful: true,
		title: 'DB snapshots',

		listViewOptions: {
			emptyText: 'No db snapshots found',
			columns: [
				{ header: "Name", width: 70, dataIndex: 'name', sortable: false, hidden: 'no' },
				{ header: "Storage", width: 25, dataIndex: 'storage', sortable: false, hidden: 'no' },
				{ header: "Created at", width: 50, dataIndex: 'dtcreated', sortable: false, hidden: 'no' },
				{ header: "Instance created at", width: 50, dataIndex: 'idtcreated', sortable: false, hidden: 'no' },
				{ header: "Status", width: 50, dataIndex: 'status', sortable: false, hidden: 'no' },
				{ header: "Port", width: 50, dataIndex: 'port', sortable: false, hidden: 'no' },
				{ header: "Placement", width: 50, dataIndex: 'avail_zone', sortable: false, hidden: 'no' },
				{ header: "Engine", width: 50, dataIndex: 'engine', sortable: false, hidden: 'no' }
			]
		},

		tbar: [
			'Region:',
			new Ext.form.ComboBox({
				allowBlank: false,
				editable: false,
				store: regions,
				value: region,
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus:false,
				width: 100,
				listeners: {
					select: function (combo, record, index) {
						panel.store.baseParams.region = combo.getValue();
						panel.store.load();
					}
				}
			})
		],

		rowOptionsMenu: [
			{ itemId: "option.launch",	text: 'Restore DB instance from this snapshot', 	href: "/aws_rds_create_instance.php?snapshot={id}"}
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
					confirmationMessage: 'Delete selected security group(s)?',
					url: '/aws_rds_snapshots.php'
				}
			],
		}
    });
});
{/literal}
</script>
{include file="inc/footer.tpl"}
