{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-script-templates-view"></div>

<script type="text/javascript">
var uid = {$smarty.session.uid};

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
				{ name: 'id', type: 'int' },
				'name', 'description', 'origin',
				{ name: 'clientid', type: 'int' },
				'approval_state', 'dtupdated', 'client_email', 'version', 'client_name'
			]
		}),
		remoteSort: true,
		url: '/server/grids/scripts_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	var panel = new Scalr.Viewers.ListView({
		renderTo: "listview-script-templates-view",
		autoRender: true,
		store: store,
		savePagingSize: true,
		saveFilter: true,
		stateId: 'listview-script-templates-view',
		stateful: true,
		title: 'Script Templates',

		tbar: [
			'&nbsp;&nbsp;Moderation phase:',
			new Ext.form.ComboBox({
				itemId: 'approval_state',
				allowBlank: true,
				editable: false, 
				store: [ ['',''], ['Approved','Approved'], ['Declined','Declined'], ['Pending','Pending'] ],
				value: '',
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus:false,
				width: 100,
				listeners: {
					select: function(combo, record, index) {
						store.baseParams.approval_state = combo.getValue(); 
						store.load();
					}
				}
			}),
			'-',
			'&nbsp;&nbsp;Origin:',
			new Ext.form.ComboBox({
				itemId: 'origin',
				allowBlank: true,
				editable: false, 
				store: [ ['',''], ['Shared','Shared'], ['Custom','Custom'], ['User-contributed','User-contributed'] ],
				value: '',
				typeAhead: false,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus:false,
				width: 150,
				listeners:{
					select: function(combo, record, index) {
						store.baseParams.origin = combo.getValue(); 
						store.load();
					}
				}
			}),
			'-',
			{
				icon: '/images/add.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Create new script template',
				handler: function() {
					document.location.href = '/script_templates.php?task=create';
				}
			}
		],

		getLocalState: function() {
			var it = {};
			it.filter_approval_state = this.getTopToolbar().getComponent('approval_state').getValue();
			it.filter_origin = this.getTopToolbar().getComponent('origin').getValue();
			return it;
		},

		// Row menu
		rowOptionsMenu: [
			{itemId: "option.execute", 		text:'Execute', 	href: "/execute_script.php?scriptid={id}"},
			new Ext.menu.Separator({itemId: "option.execSep"}),
						
			{itemId: "option.fork", 		text:'Fork', 		href: "/script_templates.php?task=fork&id={id}"},
			new Ext.menu.Separator({itemId: "option.forkSep"}),
			
			{itemId: "option.info", 		text: 'View', 	href: "/script_info.php?id={id}"},
			new Ext.menu.Separator({itemId: "option.optSep"}),

			{itemId: "option.share", 		text: 'Share', 	href: "/script_templates.php?task=share&id={id}"},
			new Ext.menu.Separator({itemId: "option.shareSep"}),

			{itemId: "option.edit", 		text: 'Edit', 	href: "/script_templates.php?task=edit&id={id}"},
			{itemId: "option.delete", 		text: 'Delete', confirmationMessage: 'Remove script?',
				menuHandler: function(item) {
					Ext.MessageBox.show({
						progress: true,
						msg: 'Removing script. Please wait...',
						wait: true,
						width: 450,
						icon: 'scalr-mb-object-removing'
					});

					Ext.Ajax.request({
						url: '/server/ajax-ui-server.php',
						success: function(response, options) {
							Ext.MessageBox.hide();

							var result = Ext.decode(response.responseText);
							if (result.result == 'ok') {
								store.load();
							} else {
								Scalr.Viewers.ErrorMessage(result.msg);
							}
						},
						params: {
							action: 'RemoveScript',
							scriptID: item.currentRecordData.id
						}
					});
				}
			}
		],
		getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (item.itemId == 'option.fork' || item.itemId == 'option.forkSep')
			{
				if (uid != 0 && (data.clientid == 0 || (data.clientid != 0 && data.clientid != uid)))
					return true;
				else
					return false;
			}
			else if (item.itemId != 'option.info')
			{
				if (item.itemId == 'option.execute' || item.itemId == 'option.execSep')
				{
					if (uid == 0)
						return false;
					else
						return true;
				}

				if ((data.clientid != 0 && data.clientid == uid) || uid == 0)
				{
					if (item.itemId == 'option.share' || item.itemId == 'option.shareSep')
					{
						if (data.origin == 'Custom' && uid != 0)
							return true;
						else
							return false;
					}
					else 
						return true;
				}
				else
					return false;
			}
			else
				return true;
		},

		listViewOptions: {
			emptyText: "No scripts defined",
			columns: [
				{ header: "Author", width: 100, dataIndex: 'id', sortable: false, hidden: 'no', tpl:
					'<tpl if="uid">' +
						'<tpl if="clientid">' +
							'<tpl if="clientid == uid">Me</tpl>' +
							'<tpl if="clientid != uid">{client_name}</tpl>' +
						'</tpl>' +
						'<tpl if="!clientid">Scalr</tpl>' +
					'</tpl>' +
					'<tpl if="!uid">' +
						'<tpl if="clientid"><a href="clients_view.php?clientid={clientid}">{client_email}</a></tpl>' +
						'<tpl if="!clientid">Scalr</tpl>' +
					'</tpl>'
				},
				{ header: "Name", width: 100, dataIndex: 'name', sortable: true, hidden: 'no' },
				{ header: "Description", width: 120, dataIndex: 'description', sortable: true, hidden: 'no' },
				{ header: "Latest version", width: 70, dataIndex: 'version', sortable: false, align:'center', hidden: 'no' },
				{ header: "Updated on", width: 70, dataIndex: 'dtupdated', sortable: false, hidden: 'no' },
				{ header: "Origin", width: '80px', dataIndex: 'origin', sortable: false, align:'center', hidden: 'no', tpl:
					'<tpl if="origin == &quot;Shared&quot;"><img src="/images/ui-ng/icons/script/default.png" title="Contributed by Scalr"></tpl>' +
					'<tpl if="origin == &quot;Custom&quot;"><img src="/images/ui-ng/icons/script/custom.png" title="Custom"></tpl>' +
					'<tpl if="origin != &quot;Shared&quot; && origin != &quot;Custom&quot;"><img src="/images/ui-ng/icons/script/contributed.png" title="Contributed by {client_name}"></tpl>'
				},
				{ header: "Moderation phase", width: '100px', dataIndex: 'approval_state', sortable: false, align:'center', hidden: 'no', tpl:
					'<tpl if="approval_state == &quot;Approved&quot; || !approval_state"><img src="/images/true.gif" title="Approved" />' +
					'<tpl if="approval_state == &quot;Pending&quot;"><img src="/images/pending.gif" title="Pending" /></tpl>' +
					'<tpl if="approval_state == &quot;Declined&quot;"><img src="/images/false.gif" title="Declined" /></tpl>'
				}
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