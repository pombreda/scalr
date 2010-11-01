{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-vpc-view"></div>
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
		renderTo: 'listview-vpc-view',
		autoRender: true,
		store: new Scalr.data.Store({
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [ 'id', 'state', 'cidrBlock', 'dhcpOptionsId' ]
			}),
			remoteSort: true,
			url: '/server/grids/aws_vpc_instances_list.php?a=1{/literal}{$grid_query_string}{literal}'
		}),
		savePagingSize: true,
		enableFilter: false,
		stateId: 'listview-vpc-view',
		stateful: true,
		title: 'Your Virtual Private Cloud',

		listViewOptions: {
			emptyText: 'No VPC clouds were found',
			columns: [
				{ header: "VPC ID", width: 70, dataIndex: 'id', sortable: false, hidden: 'no' },
				{ header: "CIDR", width: 70, dataIndex: 'cidrBlock', sortable: false, hidden: 'no' },
				{ header: "State", width: 70, dataIndex: 'state', sortable: false, hidden: 'no' },
				{ header: "DHCP Options", width: 80, dataIndex: 'dhcpOptionsId', sortable: false, hidden: 'no' }
			]
		},

		tbar: [{
			icon: '/images/add.png', // icons can also be specified inline
			cls: 'x-btn-icon',
			tooltip: 'Add new VPC',
			handler: function() {
				document.location.href = '/aws_vpc_add.php';
			}
		}],

		rowOptionsMenu: [
			{ itemId: "option.CreateSubnet",       text: 'Create a Subnet', 		href: "/aws_vpc_add_subnet.php?id={id}"},
			{ itemId: "option.attachVpnGateway",   text: 'Attach a VPN Gateway', href: "/aws_vpc_attach_vpn_gateway.php?id={id}"},
			{ itemId: "option.setDhcpOptions",  	  text: 'Set DHCP Options', 	href: "/aws_vpc_attach_dhcp.php?id={id}"}
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
					confirmationMessage: 'Delete selected VPC cloud(s)?',
					url: '/aws_vpc_view.php'
				}
			],
		}
    });
});
{/literal}
</script>
{include file="inc/footer.tpl"}
