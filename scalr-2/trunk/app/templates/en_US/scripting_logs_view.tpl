{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="search-ct"></div> 
<div style="height: 5px;"></div>
<div id="listview-scripting-logs-view"></div>

<script type="text/javascript">
{literal}
Ext.onReady(function () {
	var farms_store = new Ext.data.SimpleStore({
		fields: ['value', 'text'],
		data : {/literal}{$farms}{literal}
	});

	var searchPanel = new Ext.Panel({
		renderTo: 'search-ct',
		layout: 'form',
		labelWidth: 150,
		frame: true,
		title: 'Search',
		defaultType: 'textfield',
		
		items: [{
			itemId: 'query',
			width: 500,
			name: 'query',
			fieldLabel: 'Search string'
		}, new Ext.form.ComboBox({
			itemId: 'farmid',
			allowBlank: true,
			editable: false, 
			valueField: 'value',
			displayField: 'text',
			store: farms_store,
			fieldLabel: 'Farm',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			selectOnFocus:false
		})],
		buttons: [
			{
				text: 'Filter',
				handler: function() {
					var farmid = searchPanel.getComponent('farmid').getValue();
					store.setBaseParam('farmid', (farmid) ? farmid : '');
					store.setBaseParam('query', searchPanel.getComponent('query').getValue());
					panel.listView.setHiddenColumn('farm_name', farmid ? true : false);
					store.load();
				}
			}
		]
	});

	var store = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
			root: 'data',
			successProperty: 'success',
			errorProperty: 'error',
			totalProperty: 'total',
			id: 'id',
			fields: [
				'id','farmid','event','server_id','dtadded','message','farm_name'
			]
		}),
		remoteSort: true,
		url: 'server/grids/scripting_log_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	var panel = new Scalr.Viewers.ListView({
		renderTo: 'listview-scripting-logs-view',
		autoRender: true,
		store: store,
		enableFilter: false,
		stateId: 'listview-scripting-logs-view',
		stateful: true,
		title: 'Scripting Log {/literal}({$table_title_text}){literal}',

		listViewOptions: {
			emptyText: 'No logs found',
			columns: [
				{ header: "Time", width: 40, dataIndex: 'dtadded', sortable: false, hidden: 'no' },
				{ header: "Event", width: 35, dataIndex: 'event', sortable: false, align: 'center', hidden: 'no' },
				{ header: "Farm", width: 35, dataIndex: 'farm_name', sortable: false, hidden: 'no', tpl:
					'<a href="farms_view.php?id={farmid}">{farm_name}</a>'
				},
				{ header: "Target", width: 35, dataIndex: 'server_id', sortable: false, hidden: 'no', tpl:
					'<a href="/servers_view.php?server_id={server_id}&farmid={farmid}">{server_id}</a>'
				},
				{ header: "Message", width: 150, dataIndex: 'message', sortable: false, hidden: 'no', style: 'white-space:normal !important;' }
			]
		},

		getLocalState: function() {
			var it = {};
			it.filter_query = searchPanel.getComponent('query').getValue();
			it.filter_farmid = searchPanel.getComponent('farmid').getValue();
			return it;
		},

		listeners: {
			'render': function() {
				if (this.state && this.state.filter_query) {
					searchPanel.getComponent('query').setValue(this.state.filter_query);
					this.store.setBaseParam('query', this.state.filter_query);
				}

				if (this.state && this.state.filter_farmid) {
					searchPanel.getComponent('farmid').setValue(this.state.filter_farmid);
					this.store.setBaseParam('farmid', this.state.filter_farmid);
				}
			},
			'afterrender': function() {
				if (this.state && this.state.filter_farmid) {
					this.listView.setHiddenColumn('farm_name', this.state.filter_farmid ? true : false);
				}
			}
		}
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
