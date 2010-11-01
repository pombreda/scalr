{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-vpc-dhcp-view"></div>
<script type="text/javascript">

var FarmID = '{$smarty.get.farmid}';

var regions = [
{section name=id loop=$regions}
	['{$regions[id]}','{$regions[id]}']{if !$smarty.section.id.last},{/if}
{/section}
];

var region = '{$smarty.session.aws_region}';

{literal}
Ext.onReady(function () {
	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-vpc-dhcp-view',
		autoRender: true,
		store: new Scalr.data.Store({
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [ 'id', 'options' ]
			}),
			remoteSort: true,
			url: '/server/grids/aws_vpc_dhcp_list.php?a=1{/literal}{$grid_query_string}{literal}'
		}),
		savePagingSize: true,
		enableFilter: false,
		stateId: 'listview-vpc-dhcp-view',
		stateful: true,
		title: 'DHCP options',

		listViewOptions: {
			emptyText: 'No DHCP options were found',
			columns: [
				{ header: "DHCP Options set ID", width: 50, dataIndex: 'id', sortable: false, hidden: 'no' },
				{ header: "Options", width: 50, dataIndex: 'options', sortable: false, hidden: 'no' }
			]
		},

		tbar: [{
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Add new option',
				handler: function() {
					document.location.href = '/aws_vpc_add_dhcp.php';
				}
			 }
		],

		rowOptionsMenu: [
			{id: "option.CreateSubnet",       text: 'Configuration', 		href: "/aws_vpc_dhcp_config_view.php?id={id}"}
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
					confirmationMessage: 'Delete selected option(s)?',
					url: '/aws_vpc_dhcp_view.php'
				}
			],
		}
    });
});
{/literal}
</script>
{include file="inc/footer.tpl"}
