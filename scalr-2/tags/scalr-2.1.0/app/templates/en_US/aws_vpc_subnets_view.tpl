{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-vpc-subnets-view"></div>
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
		renderTo: 'listview-vpc-subnets-view',
		autoRender: true,
		store: new Scalr.data.Store({
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [ 'id', 'vpcId', 'state', 'cidrBlock', 'availableIpAddressCount', 'availabilityZone' ]
			}),
			remoteSort: true,
			url: '/server/grids/aws_vpc_subnet_list.php?a=1{/literal}{$grid_query_string}{literal}'
		}),
		savePagingSize: true,
		enableFilter: false,
		stateId: 'listview-vpc-subnets-view',
		stateful: true,
		title: 'Subnet list',

		listViewOptions: {
			emptyText: 'No VPC subnets were found',
			columns: [
				{ header: "Subnet ID", width: 60, dataIndex: 'id', sortable: false, hidden: 'no' },
				{ header: "VPC ID", width: 60, dataIndex: 'vpcId', sortable: false, hidden: 'no' },
				{ header: "CIDR", width: 60, dataIndex: 'cidrBlock', sortable: false, hidden: 'no' },
				{ header: "State", width: 60, dataIndex: 'state', sortable: false, hidden: 'no' },
				{ header: "Available IPs", width: 80, dataIndex: 'availableIpAddressCount', sortable: false, hidden: 'no' },
				{ header: "Available Zone", width: 80, dataIndex: 'availabilityZone', sortable: false, hidden: 'no' }
			]
		},

		withSelected: {
			menu: [
				{
					text: "Delete",
					method: 'post',
					params: {
						action: 'delete',
						with_selected: 1
					},
					confirmationMessage: 'Delete selected VPC subnet(s)?',
					url: '/aws_vpc_subnets_view.php'
				}
			],
		}
    });
});
{/literal}
</script>
{include file="inc/footer.tpl"}
