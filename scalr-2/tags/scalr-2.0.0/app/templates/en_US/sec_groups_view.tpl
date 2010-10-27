{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-sec-groups-view"></div>

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
			id: 'id',
			fields: [
				'id','name','description'
			]
		}),
		remoteSort: true,
		url: '/server/grids/sec_groups_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-sec-groups-view",
		autoRender: true,
		store: store,
		savePagingSize: true,
		saveFilter: true,
		stateId: 'listview-sec-groups-view',
		stateful: true,
		title: 'Security groups',

		tbar: [
			'Location:',
			new Ext.form.ComboBox({
				allowBlank: false,
				editable: false, 
				store: regions,
				value: region,
				displayField: 'state',
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus: false,
				width: 100,
				listeners: {
					select: function(combo, record, index) {
						store.baseParams.region = combo.getValue(); 
						store.load();
					}
				}
			}),
			'-',
			'&nbsp;&nbsp;',
			{
				itemId: 'show_all',
				xtype: 'checkbox',
				boxLabel: 'Show all security groups',
				style: 'margin: 0px',
				listeners: {
					check: function(item, checked) {
						store.baseParams.show_all = checked ? 'true' : 'false'; 
						store.load();
					}
				}
			}
		],

		getLocalState: function() {
			var it = {};
			it.filter_show_all = this.getTopToolbar().getComponent('show_all').getValue();
			return it;
		},

    	rowOptionsMenu: [
			{itemId: "option.edit", 		text:'Edit', 			  	href: "/sec_group_edit.php?name={name}"}
     	],

		listViewOptions: {
			emptyText: "No security groups found",
			columns: [
				{ header: "Name", width: 70, dataIndex: 'name', sortable: true, hidden: 'no' },
				{ header: "Description", width: 50, dataIndex: 'description', sortable: false, hidden: 'no' }
			]
		},

		withSelected: {
			menu: [
				{
					text: 'Delete',
					params: {
						with_selected: 1,
						action: 'delete'
					},
					confirmationMessage: 'Remove selected groups?'
				}
			]
		},

		listeners: {
			'render': function() {
				if (this.state && this.state.filter_show_all) {
					this.getTopToolbar().getComponent('show_all').setValue(this.state.filter_show_all);
					this.store.baseParams.show_all = this.state.filter_show_all;
				}
			}
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}