{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-roles-view"></div>

<script type="text/javascript">

var uid = '{$Scalr_Session->getClientId()}';

var locations = [
{foreach from=$locations name=id key=key item=item}
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
				{name: 'client_id', type: 'int'},
				'name', 'origin', 'architecture', 'approval_state',
				'client_name', 'behaviors', 'os', 'platforms','generation'
			]
		}),
		remoteSort: true,
		url: '/server/grids/roles_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-roles-view",
		autoRender: true,
		store: store,
		savePagingSize: true,
		savePagingNumber: true,
		saveFilter: true,
		stateId: 'listview-roles-view-5',
		//stateful: true,
		title: 'Roles',

		tbar: [
			' ',
			'Location:',
			new Ext.form.ComboBox({
				itemId: 'cloud_location',
				allowBlank: false,
				editable: false, 
				store: locations,
				value: region,
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus: false,
				width: 150,
				listeners: {
					select: function(combo, record, index) {
						store.baseParams.cloud_location = combo.getValue();
						store.load();
					}
				}
			}),
			'-', ' ',
			'Moderation phase:',
			new Ext.form.ComboBox({
				itemId: 'approval_state',
				allowBlank: true,
				editable: false, 
				store: [['',''],['Approved','Approved'],['Declined','Declined'],['Pending','Pending']],
				value: '',
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus: false,
				width: 100,
				listeners:{
					select: function(combo, record, index) {
						store.baseParams.approval_state = combo.getValue();
						store.load();
					}
				}
			}),
			'-', ' ',
			'Origin:',
			new Ext.form.ComboBox({
				itemId: 'origin',
				allowBlank: true,
				editable: false, 
				store: [['',''],['Shared','Shared'],['Custom','Custom'],['User-contributed','User-contributed']],
				value: '',
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus: false,
				emptyText: ' ',
				width: 150,
				listeners: {
					select: function(combo, record, index) {
						store.baseParams.origin = combo.getValue(); 
						store.load();
					}
				}
			})
		],

		getLocalState: function() {
			var it = {};
			it.filter_approval_state = this.getTopToolbar().getComponent('approval_state').getValue();
			it.filter_origin = this.getTopToolbar().getComponent('origin').getValue();
			return it;
		},
		
		// Row menu
		rowOptionsMenu: [
			{itemId: "option.view", 		text:'View details', 			  	menuHandler: function(menuItem) {
				var loadMask = new Ext.LoadMask(Ext.getBody(), { msg: "Loading role information ..." });
				loadMask.show();
				Ext.Ajax.request({
					url: '/server/ajax-ui-server.php',
					params: { action: 'GetRoleInfo', roleId: menuItem.currentRecordData.id },
					success: function (response) {
						var result = Ext.decode(response.responseText);
						if (result.result == 'ok') {
							var win = new Ext.Window({
								title: 'Role information',
								html: result.data,
								modal: true,
								draggable: false,
								resizable: false,
								bodyStyle: 'background-color: white'
							});
							win.show();
						}
						loadMask.hide();
					},
					failure: function() {
						loadMask.hide();
					}
				});
			}},
			{itemId: "option.edit", 		text:'Edit', 			  			href: "/role_edit.php?id={id}"},
			new Ext.menu.Separator({itemId: "option.editSep"}),
			{itemId: "option.switch", 		text:'Switch AMI', 			  			href: "/role_edit.php?task=switch&id={id}"},
			new Ext.menu.Separator({itemId: "option.switchSep"}),
			{itemId: "option.share", 		text:'Share this role', 			href: "/role_edit.php?task=share&id={id}"}
		],
		getRowOptionVisibility: function (item, record) {
			if (item.itemId == 'option.view')
				return true;

			if (record.data.origin == 'CUSTOM') {
				if (item.itemId == 'option.edit' || item.itemId == 'option.editSep' || item.itemId == 'option.share' || item.itemId == 'option.shareSep' || item.itemId == 'option.switch' || item.itemId == 'option.switchSep') {
					if (uid != 0)
						return true;
					else
						return false;
				}

				return true;
			}
			else
				return false;
		},
		/*
		withSelected: {
			menu: [
				{
					text: "Delete",
					params: {
						with_selected: 1,
						action: 'delete'
					}
				}
			]
		},
		*/

		listViewOptions: {
			emptyText: "No roles found",
			columns: [
				{ header: "Role name", width: 50, dataIndex: 'name', sortable: true, hidden: 'no'},
				{ header: "OS", width: 25, dataIndex: 'os', sortable: true, hidden: 'no'},
				{ header: "Owner", width: 30, dataIndex: 'client_id', sortable: false, hidden: 'no',
					tpl: '<tpl if="uid == 0 && client_id != &quot;&quot;"><a href="clients_view.php?client_id={client_id}"></tpl>{client_name}<tpl if="uid == 0 && client_id != &quot;&quot;"></a></tpl>' },
				{ header: "Behaviors", width: 40, dataIndex: 'behaviors', sortable: false, hidden: 'no'},
				{ header: "Available on", width: 60, dataIndex: 'platforms', sortable: false, hidden: 'no'},
				{ header: "Arch", width: 15, dataIndex: 'architecture', sortable: true, hidden: 'no'},
				{ header: "Scalr agent", width: 10, dataIndex: 'generation', sortable: false, hidden: 'no'},
				{ header: "Contributed", width: 10, dataIndex: 'contributed', sortable: false, hidden: 'yes', align: 'center', 
					tpl: '<tpl if="origin == &quot;SHARED&quot;"><img src="/images/true.gif"></tpl><tpl if="origin != &quot;SHARED&quot;""><img src="/images/false.gif"></tpl>' },
				{ header: "Moderation phase", width: 10, dataIndex: 'moderation', sortable: false, hidden: 'yes', align: 'center',
					tpl:	'<tpl if="approval_state == &quot;Approved&quot;"><img src="/images/true.gif" title="Approved" /></tpl>' +
							'<tpl if="approval_state == &quot;Pending&quot;"><img src="/images/pending.gif" title="Pending" /></tpl>' +
							'<tpl if="approval_state == &quot;Declined&quot;"><img src="/images/false.gif" title="Declined" />' }
			]
		},

		listeners: {
			'render': function() {
				if (this.state && this.state.filter_approval_state) {
					this.getTopToolbar().getComponent('approval_state').setValue(this.state.filter_approval_state);
					this.store.baseParams.approval_state = this.state.filter_approval_state;
				}

				if (this.state && this.state.filter_origin) {
					this.getTopToolbar().getComponent('origin').setValue(this.state.filter_origin);
					this.store.baseParams.origin = this.state.filter_origin;
				}
			}
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}