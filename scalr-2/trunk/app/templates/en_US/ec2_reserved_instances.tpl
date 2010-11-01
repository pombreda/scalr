{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-ec2-reserved-instances-view"></div>
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
		renderTo: 'listview-ec2-reserved-instances-view',
		autoRender: true,
		store: new Scalr.data.Store({
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [
					'id', 'instance_type', 'avail_zone', 'duration',
					'usage_price', 'fixed_price', 'instance_count', 'description', 'state'
				]
			}),
			remoteSort: true,
			url: '/server/grids/reserved_instances_list.php?a=1{/literal}{$grid_query_string}{literal}'
		}),
		savePagingSize: true,
		enableFilter: false,
		stateId: 'listview-ec2-reserved-instances-view',
		stateful: true,
		title: 'Reserved instances',

		listViewOptions: {
			emptyText: 'No reserved instances found',
			columns: [
				{ header: "ID", width: 115, dataIndex: 'id', sortable: true, hidden: 'no' },
				{ header: "Type", width: 35, dataIndex: 'instance_type', sortable: false, hidden: 'no' },
				{ header: "Placement", width: 35, dataIndex: 'avail_zone', sortable: true, hidden: 'no' },
				{ header: "Duration", width: 35, dataIndex: 'duration', sortable: false, align:'center', hidden: 'no', tpl:
					'<tpl if="duration == 1">{duration} year</tpl><tpl uf="duration != 1">{duration} years</tpl>'
				},
				{ header: "Usage Price", width: 40, dataIndex: 'usage_price', sortable: false, align:'center', hidden: 'no', tpl: '${usage_price}' },
				{ header: "Fixed Price", width: 35, dataIndex: 'fixed_price', sortable: false, align:'center', hidden: 'no', tpl: '${fixed_price}' },
				{ header: "Count", width: 25, dataIndex: 'instance_count', sortable: false, align:'center', hidden: 'no' },
				{ header: "Description", width: 50, dataIndex: 'description', sortable: false, hidden: 'no' },
				{ header: "State", width: 50, dataIndex: 'state', sortable: false, hidden: 'no' }
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
		]
    });
});
{/literal}
</script>
{include file="inc/footer.tpl"}
